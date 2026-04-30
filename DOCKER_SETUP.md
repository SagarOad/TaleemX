# TaleemX Docker Setup

## Local run

1. Copy env template:
   - `cp .env.example .env`
2. Update secrets in `.env`:
   - `DB_PASSWORD`
   - `MYSQL_ROOT_PASSWORD`
   - `GEMINI_API_KEY` and other AI keys
3. Start stack:
   - `docker compose up -d --build`
4. Access services:
   - PHP LMS: `http://localhost:8080`
   - AI service health: `http://localhost:5050/health`

## Server structure

Deploy this repository to:

- `/home/ubuntu/TaleemX-Project`
  - `docker-compose.yml`
  - `.env`
  - `taleemx-php/Dockerfile`
  - `lms-ai-service/Dockerfile`
  - app folders

## CI/CD secrets

Configure these GitHub repository secrets:

- `SERVER_HOST`
- `SERVER_USER`
- `SERVER_SSH_KEY`
- `SERVER_PORT` (optional, defaults to `22`)

When `main`/`master` is updated (or manual trigger), deployment workflow will:

1. SSH into server
2. `git pull`
3. `docker compose up -d --build`
