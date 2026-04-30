"""
services/subtitle_service.py
Subtitle extraction from:
  1. Uploaded video/audio files via OpenAI Whisper
  2. YouTube URLs via RapidAPI (youtube-transcripts)
"""

import logging
import os
import re
import tempfile
from typing import Optional
import requests

from config import Config

logger = logging.getLogger(__name__)


def _get_youtube_video_id(url: str) -> Optional[str]:
    """Extract YouTube video ID from various URL formats."""
    # Patterns: youtu.be/ID, youtube.com/watch?v=ID, youtube.com/shorts/ID
    patterns = [
        r"youtu\.be/([A-Za-z0-9_-]{11})",
        r"youtube\.com/watch\?.*v=([A-Za-z0-9_-]{11})",
        r"youtube\.com/embed/([A-Za-z0-9_-]{11})",
        r"youtube\.com/shorts/([A-Za-z0-9_-]{11})",
        r"youtube\.com/v/([A-Za-z0-9_-]{11})",
    ]
    for pattern in patterns:
        match = re.search(pattern, url)
        if match:
            return match.group(1)
    return None


def _allowed_extension(filename: str) -> bool:
    ext = filename.rsplit(".", 1)[-1].lower() if "." in filename else ""
    return ext in Config.ALLOWED_VIDEO_EXTENSIONS


class SubtitleService:
    def _safe_float(self, value, default: float = 0.0) -> float:
        try:
            return float(value)
        except (TypeError, ValueError):
            return default

    # ── YouTube ───────────────────────────────────────────────────────────────
    def extract_from_youtube(self, url: str) -> tuple[str, list[dict], str, Optional[str]]:
        """
        Fetch subtitles from a YouTube URL via RapidAPI.

        Returns: (subtitles_text, segments, source, error_message)
        """
        video_id = _get_youtube_video_id(url)
        if not video_id:
            return "", [], "", "Could not extract a valid YouTube video ID from the URL provided."

        if not Config.RAPIDAPI_KEY:
            return "", [], "", "RAPIDAPI_KEY is not configured for YouTube transcript extraction."

        logger.info("Fetching YouTube transcript via RapidAPI for video_id=%s", video_id)
        endpoint = os.getenv(
            "RAPIDAPI_URL",
            "https://youtube-transcripts.p.rapidapi.com/youtube/transcript",
        )
        params = {
            "url": url,
            "videoId": video_id,
            "chunkSize": Config.RAPIDAPI_YT_CHUNK_SIZE,
            "text": str(Config.RAPIDAPI_YT_TEXT_MODE).lower(),
            "lang": Config.RAPIDAPI_YT_LANG,
        }
        headers = {
            "Content-Type": "application/json",
            "x-rapidapi-host": Config.RAPIDAPI_HOST,
            "x-rapidapi-key": Config.RAPIDAPI_KEY,
        }

        try:
            response = requests.get(
                endpoint,
                params=params,
                headers=headers,
                timeout=Config.RAPIDAPI_TIMEOUT_SECONDS,
            )
            response.raise_for_status()
            payload = response.json()
        except requests.Timeout:
            return "", [], "", "RapidAPI request timed out while fetching YouTube transcript."
        except requests.RequestException as exc:
            logger.exception("RapidAPI YouTube transcript request failed: %s", exc)
            return "", [], "", f"RapidAPI YouTube transcript request failed: {str(exc)}"
        except ValueError:
            return "", [], "", "RapidAPI returned a non-JSON response."

        segments = self._rapidapi_payload_to_segments(payload)
        text = " ".join(seg["text"] for seg in segments).strip()
        if not text:
            # Some RapidAPI responses return text directly when text=true or on certain plans.
            text = self._rapidapi_payload_to_text(payload)
            if text:
                segments = [{
                    "id": 1,
                    "start": 0.0,
                    "end": 0.0,
                    "duration": 0.0,
                    "text": text,
                }]
        if not text:
            return "", [], "", "RapidAPI returned no transcript text for this video."
        logger.info("RapidAPI YouTube transcript extracted: %d characters.", len(text))
        return text, segments, "rapidapi_youtube_transcripts", None

    def _rapidapi_payload_to_segments(self, payload) -> list[dict]:
        """
        Normalize RapidAPI response items to our segment contract:
        {id, start, end, duration, text}
        """
        entries = self._extract_rapidapi_entries(payload)
        if not entries:
            return []

        segments = []
        for i, entry in enumerate(entries):
            if not isinstance(entry, dict):
                continue

            raw_text = (
                entry.get("text")
                or entry.get("caption")
                or entry.get("subtitle")
                or entry.get("snippet")
                or ""
            )
            cleaned_text = re.sub(r"\[.*?\]", "", str(raw_text)).strip()
            if not cleaned_text:
                continue
            # Match Whisper/file output style: single-line caption text.
            cleaned_text = re.sub(r"\s+", " ", cleaned_text).strip()

            start = self._safe_float(
                entry.get("start", entry.get("startTime", entry.get("offset", 0.0)))
            )
            duration = self._safe_float(
                entry.get("duration", entry.get("dur", entry.get("length", 0.0)))
            )
            end = self._safe_float(entry.get("end"), start + duration if duration > 0 else start)

            # Some providers return milliseconds for time fields; normalize to seconds.
            if max(start, duration, end) > 1000:
                start /= 1000.0
                duration /= 1000.0
                end /= 1000.0

            if end < start:
                end = start
                duration = 0.0

            segments.append({
                "id": i + 1,
                "start": round(start, 3),
                "end": round(end, 3),
                "duration": round(max(duration, 0.0), 3),
                "text": cleaned_text,
            })
        return segments

    def _extract_rapidapi_entries(self, payload) -> list[dict]:
        """
        Collect transcript entry lists from varied RapidAPI response shapes.
        """
        queue = [payload]
        candidate_list_keys = (
            "transcript",
            "captions",
            "subtitles",
            "chunks",
            "data",
            "items",
            "results",
            "content",
            "lines",
        )

        while queue:
            node = queue.pop(0)
            if isinstance(node, list):
                if node and all(isinstance(x, dict) for x in node):
                    has_text_like = any(
                        any(k in item for k in ("text", "caption", "subtitle", "snippet", "line"))
                        for item in node
                    )
                    if has_text_like:
                        return node
                for item in node:
                    if isinstance(item, (dict, list)):
                        queue.append(item)
                continue

            if isinstance(node, dict):
                for key in candidate_list_keys:
                    maybe = node.get(key)
                    if isinstance(maybe, list):
                        queue.append(maybe)
                for value in node.values():
                    if isinstance(value, dict):
                        queue.append(value)

        return []

    def _rapidapi_payload_to_text(self, payload) -> str:
        """
        Extract full transcript text when API returns plain text-oriented shapes.
        """
        if isinstance(payload, str):
            return payload.strip()
        if not isinstance(payload, dict):
            return ""

        text_keys = ("text", "transcriptText", "transcript", "content", "fullText")
        for key in text_keys:
            value = payload.get(key)
            if isinstance(value, str) and value.strip():
                return value.strip()

        data = payload.get("data")
        if isinstance(data, str) and data.strip():
            return data.strip()
        if isinstance(data, dict):
            for key in text_keys:
                value = data.get(key)
                if isinstance(value, str) and value.strip():
                    return value.strip()
        return ""

    # ── Video / Audio File (Whisper) ─────────────────────────────────────────
    def extract_from_file(self, file_obj) -> tuple[str, list[dict], str, Optional[str]]:
        """
        Transcribe an uploaded video/audio file using OpenAI Whisper.

        Returns: (subtitles_text, segments, source, error_message)
        """
        filename = file_obj.filename or "upload"

        if not _allowed_extension(filename):
            return "", [], "", (
                f"Unsupported file type. Allowed types: "
                f"{', '.join(sorted(Config.ALLOWED_VIDEO_EXTENSIONS))}"
            )

        # Ensure upload directory exists
        os.makedirs(Config.UPLOAD_FOLDER, exist_ok=True)

        ext = filename.rsplit(".", 1)[-1].lower()
        tmp_path = None

        try:
            # Save uploaded file to temp location
            with tempfile.NamedTemporaryFile(
                dir=Config.UPLOAD_FOLDER,
                suffix=f".{ext}",
                delete=False
            ) as tmp:
                tmp_path = tmp.name
                file_obj.save(tmp_path)
                file_size_mb = os.path.getsize(tmp_path) / (1024 * 1024)
                logger.info("Saved upload to %s (%.1f MB)", tmp_path, file_size_mb)

            # Transcribe with Whisper
            text, segments, source, error = self._whisper_transcribe(tmp_path)
            return text, segments, source, error

        except Exception as exc:
            logger.exception("File subtitle extraction failed: %s", exc)
            return "", [], "", f"Subtitle extraction failed: {str(exc)}"

        finally:
            # Always clean up temp file
            if tmp_path and os.path.exists(tmp_path):
                try:
                    os.remove(tmp_path)
                    logger.debug("Cleaned up temp file: %s", tmp_path)
                except OSError:
                    pass

    def _whisper_transcribe(self, file_path: str) -> tuple[str, list[dict], str, Optional[str]]:
        """Run transcription using whisper-compatible engines on a local file path."""
        logger.info("Loading transcription model: %s", Config.WHISPER_MODEL)
        cache_root = os.path.join(Config.UPLOAD_FOLDER, ".model_cache")
        os.makedirs(cache_root, exist_ok=True)
        os.environ.setdefault("HOME", Config.UPLOAD_FOLDER)
        os.environ.setdefault("XDG_CACHE_HOME", cache_root)
        os.environ.setdefault("HF_HOME", os.path.join(cache_root, "hf"))
        os.environ.setdefault("TRANSFORMERS_CACHE", os.path.join(cache_root, "transformers"))

        # Prefer openai-whisper if present, then fall back to faster-whisper.
        try:
            import whisper  # type: ignore
            model = whisper.load_model(Config.WHISPER_MODEL)
            logger.info("Running openai-whisper transcription on: %s", file_path)
            result = model.transcribe(file_path, verbose=False)
            text = result.get("text", "").strip()
            segments = []
            for i, segment in enumerate(result.get("segments", [])):
                seg_text = str(segment.get("text", "")).strip()
                if not seg_text:
                    continue
                start = float(segment.get("start", 0.0))
                end = float(segment.get("end", start))
                segments.append({
                    "id": i + 1,
                    "start": round(start, 3),
                    "end": round(end, 3),
                    "duration": round(max(end - start, 0.0), 3),
                    "text": seg_text,
                })
            source = "openai_whisper"
        except ImportError:
            try:
                from faster_whisper import WhisperModel  # type: ignore
            except ImportError:
                return "", [], "", (
                    "Whisper backend is not installed. Install either "
                    "'openai-whisper' or 'faster-whisper'."
                )
            try:
                model = WhisperModel(Config.WHISPER_MODEL, device="cpu", compute_type="int8")
                logger.info("Running faster-whisper transcription on: %s", file_path)
                segments, _ = model.transcribe(file_path, vad_filter=True)
                timed_segments = []
                for i, segment in enumerate(segments):
                    seg_text = segment.text.strip() if segment.text else ""
                    if not seg_text:
                        continue
                    start = float(segment.start)
                    end = float(segment.end)
                    timed_segments.append({
                        "id": i + 1,
                        "start": round(start, 3),
                        "end": round(end, 3),
                        "duration": round(max(end - start, 0.0), 3),
                        "text": seg_text,
                    })
                text = " ".join(seg["text"] for seg in timed_segments).strip()
                segments = timed_segments
                source = "faster_whisper"
            except Exception as exc:
                logger.exception("faster-whisper transcription error: %s", exc)
                return "", [], "", f"Transcription error: {str(exc)}"
        except Exception as exc:
            logger.exception("openai-whisper transcription error: %s", exc)
            return "", [], "", f"Transcription error: {str(exc)}"

        if not text:
            return "", [], "", "Transcription returned empty text. The audio may be silent or too short."
        logger.info("Transcription complete: %d characters.", len(text))
        return text, segments, source, None

