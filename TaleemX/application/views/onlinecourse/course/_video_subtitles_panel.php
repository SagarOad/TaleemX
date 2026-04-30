<?php
/**
 * Re-usable partial that renders a click-to-seek transcript panel under a
 * video player and ensures <track> subtitles are attached to HTML5 videos.
 *
 * Required variables:
 *   $sub_entity_type   - 'course' | 'lesson'
 *   $sub_entity_id     - int (0 means "no transcript, render nothing")
 *   $sub_provider      - 'file' | 'html5' | 'youtube' | 'vimeo' | 's3_bucket'
 *   $sub_video_el_id   - DOM id of the <video> or <iframe> element
 *
 * Optional:
 *   $sub_panel_title   - heading text (defaults to "Transcript")
 *   $sub_segments      - pre-fetched segments array (skips AJAX fetch)
 *   $sub_track_mode    - 'showing' | 'hidden' (default 'hidden' to match native CC UX)
 *
 * If there is no transcript yet for the entity, the partial renders nothing.
 */

if (!isset($sub_entity_type) || !in_array($sub_entity_type, array('course', 'lesson'), true)) { return; }
$sub_entity_id = isset($sub_entity_id) ? (int) $sub_entity_id : 0;
if ($sub_entity_id <= 0) { return; }

if (!isset($sub_video_el_id) || $sub_video_el_id === '') { return; }
$sub_provider    = isset($sub_provider) ? strtolower((string) $sub_provider) : '';
$sub_panel_title = isset($sub_panel_title) ? (string) $sub_panel_title : 'Transcript';
$sub_track_mode  = isset($sub_track_mode) ? (string) $sub_track_mode : 'hidden';

// Only render if there is actually a transcript stored. Inside a CodeIgniter
// view `$this` is the Loader, which only mirrors the controller's properties
// at render start, so a model loaded from inside this partial wouldn't show
// up on `$this`. We fetch the CI instance explicitly and fail gracefully if
// anything is missing (e.g. on courses/lessons that don't have subtitles yet)
// so the host page never 500s because of this panel.
$__vtCI = function_exists('get_instance') ? get_instance() : null;
$vt_model = null;
if ($__vtCI !== null) {
    if (!isset($__vtCI->video_transcript_model)) {
        try {
            $__vtCI->load->model('video_transcript_model');
        } catch (\Throwable $e) {
            log_message('error', 'vtpanel: failed to load video_transcript_model: ' . $e->getMessage());
            return;
        } catch (\Exception $e) {
            log_message('error', 'vtpanel: failed to load video_transcript_model: ' . $e->getMessage());
            return;
        }
    }
    $vt_model = isset($__vtCI->video_transcript_model) ? $__vtCI->video_transcript_model : null;
}
if ($vt_model === null || !class_exists('Video_transcript_model', false)) {
    return;
}

$entity_const = ($sub_entity_type === 'course')
    ? Video_transcript_model::ENTITY_COURSE
    : Video_transcript_model::ENTITY_LESSON;

$segments_for_render = array();
try {
    $segments_for_render = isset($sub_segments) && is_array($sub_segments)
        ? $sub_segments
        : $vt_model->get_segments($entity_const, $sub_entity_id);
} catch (\Throwable $e) {
    log_message('error', 'vtpanel: get_segments failed: ' . $e->getMessage());
    return;
} catch (\Exception $e) {
    log_message('error', 'vtpanel: get_segments failed: ' . $e->getMessage());
    return;
}
if (empty($segments_for_render)) { return; }

$vtt_url = base_url() . 'onlinecourse/' . ($sub_entity_type === 'course' ? 'course' : 'courselesson')
    . '/subtitles_vtt/' . $sub_entity_id;

// Unique DOM id so multiple panels can coexist on the same page.
$panel_id = 'vtpanel_' . $sub_entity_type . '_' . $sub_entity_id;
?>
<div class="vtpanel" id="<?php echo $panel_id; ?>"
     data-video-el="<?php echo htmlspecialchars($sub_video_el_id, ENT_QUOTES, 'UTF-8'); ?>"
     data-provider="<?php echo htmlspecialchars($sub_provider, ENT_QUOTES, 'UTF-8'); ?>"
     data-entity-type="<?php echo htmlspecialchars($sub_entity_type, ENT_QUOTES, 'UTF-8'); ?>"
     data-entity-id="<?php echo (int) $sub_entity_id; ?>"
     data-vtt-url="<?php echo htmlspecialchars($vtt_url, ENT_QUOTES, 'UTF-8'); ?>"
     data-track-mode="<?php echo htmlspecialchars($sub_track_mode, ENT_QUOTES, 'UTF-8'); ?>"
     style="margin-top:12px; border:1px solid #e1e4e8; border-radius:6px; background:#fafbfc;">
    <div class="vtpanel-header" style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; cursor:pointer; border-bottom:1px solid #e1e4e8;">
        <div style="font-weight:600; color:#24292e;">
            <i class="fa fa-closed-captioning"></i> <?php echo htmlspecialchars($sub_panel_title, ENT_QUOTES, 'UTF-8'); ?>
            <small class="text-muted" style="font-weight:400; margin-left:6px;">
                (<?php echo count($segments_for_render); ?> segments)
            </small>
        </div>
        <div class="vtpanel-tools" style="display:flex; align-items:center; gap:8px;">
            <input type="text" class="vtpanel-search form-control input-sm"
                   placeholder="Search transcript..."
                   style="width:180px; height:28px; font-size:12px;"
                   onclick="event.stopPropagation();" />
            <button type="button" class="btn btn-xs btn-default vtpanel-toggle" onclick="event.stopPropagation();">
                <i class="fa fa-chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="vtpanel-body" style="max-height:260px; overflow-y:auto; padding:6px 0;">
        <?php foreach ($segments_for_render as $seg):
            if (!is_array($seg)) { continue; }
            $start = isset($seg['start']) ? (float) $seg['start'] : 0;
            $text  = isset($seg['text']) ? trim((string) $seg['text']) : '';
            if ($text === '') { continue; }
            $mm    = (int) floor($start / 60);
            $ss    = (int) floor($start - $mm * 60);
            $stamp = sprintf('%d:%02d', $mm, $ss);
        ?>
        <div class="vtpanel-row" data-start="<?php echo $start; ?>"
             style="padding:4px 12px; cursor:pointer; display:flex; gap:10px; line-height:1.45;">
            <span class="vtpanel-ts" style="color:#0366d6; font-family:monospace; min-width:44px; flex-shrink:0;">
                <?php echo $stamp; ?>
            </span>
            <span class="vtpanel-text" style="color:#24292e;">
                <?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
// If the panel is injected into a page that already has the helper script
// loaded (e.g. via AJAX swap of the lesson video), bind it eagerly.
if (defined('VTPANEL_SCRIPT_EMITTED')) { ?>
<script>
(function () {
    if (window.VTPanel && typeof window.VTPanel.bind === 'function') {
        var el = document.getElementById(<?php echo json_encode($panel_id); ?>);
        if (el) { window.VTPanel.bind(el); }
    }
})();
</script>
<?php return; } ?>
<?php
// First render on the page: emit the shared script + styles exactly once.
define('VTPANEL_SCRIPT_EMITTED', true);
?>
<style>
.vtpanel-row:hover { background:#eef5ff; }
.vtpanel-row.active { background:#dbeafe; }
.vtpanel-row.active .vtpanel-ts { font-weight:700; }
.vtpanel-hidden { display:none !important; }
</style>
<script>
(function () {
    "use strict";

    // Lazily load the YouTube IFrame API (once per page) so we can seek and
    // poll the current time on YouTube players.
    var ytApiLoading = false;
    var ytApiReadyCallbacks = [];
    function loadYouTubeApi(cb) {
        if (window.YT && window.YT.Player) { cb(); return; }
        ytApiReadyCallbacks.push(cb);
        if (ytApiLoading) { return; }
        ytApiLoading = true;
        var prev = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = function () {
            if (typeof prev === 'function') { try { prev(); } catch (_) {} }
            var cbs = ytApiReadyCallbacks.slice();
            ytApiReadyCallbacks.length = 0;
            for (var i = 0; i < cbs.length; i++) {
                try { cbs[i](); } catch (_) {}
            }
        };
        var s = document.createElement('script');
        s.src = 'https://www.youtube.com/iframe_api';
        (document.head || document.body).appendChild(s);
    }

    function ensureYoutubeJsApi(iframe) {
        if (!iframe || !iframe.src) { return; }
        // Add enablejsapi=1 if missing so YT.Player can attach.
        if (iframe.src.indexOf('enablejsapi=1') === -1) {
            iframe.src = iframe.src + (iframe.src.indexOf('?') === -1 ? '?' : '&') + 'enablejsapi=1';
        }
        // Remove autoplay so reinjected src doesn't auto-play when the user
        // hasn't asked for it yet. (We only touch src if we had to add the flag.)
    }

    function attachHtml5Track(video, vttUrl, mode) {
        if (!video || !vttUrl) { return; }
        // Avoid adding duplicate tracks when the panel initialises multiple times.
        var existing = video.querySelectorAll('track[data-vt-auto="1"]');
        for (var i = 0; i < existing.length; i++) { existing[i].remove(); }

        var track = document.createElement('track');
        track.kind   = 'subtitles';
        track.label  = 'Transcript';
        track.srclang = 'en';
        track.src    = vttUrl;
        track.setAttribute('data-vt-auto', '1');
        track.default = (mode === 'showing');
        video.appendChild(track);
        video.addEventListener('loadedmetadata', function () {
            try {
                // Force track visibility if requested.
                if (mode === 'showing' && video.textTracks && video.textTracks.length) {
                    for (var j = 0; j < video.textTracks.length; j++) {
                        if (video.textTracks[j].kind === 'subtitles') {
                            video.textTracks[j].mode = 'showing';
                        }
                    }
                }
            } catch (_) {}
        });
    }

    function bindPanel(panel) {
        if (panel.__vtBound) { return; }
        panel.__vtBound = true;

        var videoElId = panel.getAttribute('data-video-el');
        var provider  = (panel.getAttribute('data-provider') || '').toLowerCase();
        var vttUrl    = panel.getAttribute('data-vtt-url');
        var trackMode = panel.getAttribute('data-track-mode') || 'hidden';
        var target    = document.getElementById(videoElId);
        if (!target) { return; }

        var isHtml5 = (target.tagName && target.tagName.toLowerCase() === 'video');
        var isIframe = (target.tagName && target.tagName.toLowerCase() === 'iframe');

        // ---- Attach captions to HTML5 video ----------------------------
        if (isHtml5 && vttUrl) {
            attachHtml5Track(target, vttUrl, trackMode);
        }

        // ---- YouTube player reference for seek/time polling ------------
        var ytPlayer = null;
        if (isIframe && provider === 'youtube') {
            ensureYoutubeJsApi(target);
            loadYouTubeApi(function () {
                try { ytPlayer = new YT.Player(target); } catch (_) {}
            });
        }

        // ---- Click-to-seek ---------------------------------------------
        var rows = panel.querySelectorAll('.vtpanel-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].addEventListener('click', function () {
                var t = parseFloat(this.getAttribute('data-start')) || 0;
                if (isHtml5) {
                    try {
                        target.currentTime = t;
                        var p = target.play();
                        if (p && typeof p.catch === 'function') { p.catch(function () {}); }
                    } catch (_) {}
                } else if (ytPlayer && typeof ytPlayer.seekTo === 'function') {
                    try { ytPlayer.seekTo(t, true); ytPlayer.playVideo && ytPlayer.playVideo(); } catch (_) {}
                } else if (isIframe && target.contentWindow) {
                    try {
                        target.contentWindow.postMessage(JSON.stringify({
                            event: 'command', func: 'seekTo', args: [t, true]
                        }), '*');
                    } catch (_) {}
                }
            });
        }

        // ---- Auto-highlight the current segment ------------------------
        function highlightForTime(t) {
            var activeIdx = -1;
            for (var i = 0; i < rows.length; i++) {
                var s  = parseFloat(rows[i].getAttribute('data-start')) || 0;
                var ns = (i + 1 < rows.length)
                    ? (parseFloat(rows[i + 1].getAttribute('data-start')) || (s + 9999))
                    : (s + 9999);
                if (t >= s && t < ns) { activeIdx = i; break; }
            }
            for (var j = 0; j < rows.length; j++) {
                if (j === activeIdx) {
                    if (!rows[j].classList.contains('active')) {
                        rows[j].classList.add('active');
                        // Auto-scroll into view (only within the panel body)
                        var body = panel.querySelector('.vtpanel-body');
                        if (body) {
                            var rel = rows[j].offsetTop - body.offsetTop;
                            if (rel < body.scrollTop || rel > body.scrollTop + body.clientHeight - 40) {
                                body.scrollTop = rel - 40;
                            }
                        }
                    }
                } else {
                    rows[j].classList.remove('active');
                }
            }
        }

        if (isHtml5) {
            target.addEventListener('timeupdate', function () {
                highlightForTime(target.currentTime || 0);
            });
        } else if (isIframe && provider === 'youtube') {
            setInterval(function () {
                try {
                    if (ytPlayer && typeof ytPlayer.getCurrentTime === 'function') {
                        highlightForTime(ytPlayer.getCurrentTime() || 0);
                    }
                } catch (_) {}
            }, 500);
        }

        // ---- Search filter ---------------------------------------------
        var searchInput = panel.querySelector('.vtpanel-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = (this.value || '').trim().toLowerCase();
                for (var i = 0; i < rows.length; i++) {
                    var t = rows[i].innerText.toLowerCase();
                    if (!q || t.indexOf(q) !== -1) {
                        rows[i].classList.remove('vtpanel-hidden');
                    } else {
                        rows[i].classList.add('vtpanel-hidden');
                    }
                }
            });
        }

        // ---- Collapse/expand -------------------------------------------
        var header = panel.querySelector('.vtpanel-header');
        var body   = panel.querySelector('.vtpanel-body');
        var toggle = panel.querySelector('.vtpanel-toggle');
        function setCollapsed(collapsed) {
            if (!body) { return; }
            body.style.display = collapsed ? 'none' : '';
            if (toggle) {
                toggle.innerHTML = collapsed
                    ? '<i class="fa fa-chevron-down"></i>'
                    : '<i class="fa fa-chevron-up"></i>';
            }
        }
        if (header) {
            header.addEventListener('click', function (e) {
                if (e.target && (e.target.classList.contains('vtpanel-search')
                    || e.target.closest('.vtpanel-tools'))) { return; }
                setCollapsed(body && body.style.display !== 'none');
            });
        }
    }

    function bindAllPanels(root) {
        var scope = root || document;
        var panels = scope.querySelectorAll('.vtpanel');
        for (var i = 0; i < panels.length; i++) { bindPanel(panels[i]); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindAllPanels(); });
    } else {
        bindAllPanels();
    }

    // Expose a small helper so views that inject players via AJAX (e.g. the
    // lesson player swap-in on the student course page) can re-bind.
    window.VTPanel = { bindAll: bindAllPanels, bind: bindPanel };
})();
</script>

