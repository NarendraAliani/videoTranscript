#!/usr/bin/env python3
"""Download publicly accessible media with yt-dlp and transcribe it through Groq.
Use only content you own or are authorised to process. No DRM/login circumvention.
"""
import json
import os
import sys
import tempfile
from pathlib import Path

import requests
from yt_dlp import YoutubeDL


def main() -> int:
    try:
        payload = json.load(sys.stdin)
        url = payload.get("url", "")
        language = payload.get("language", "auto")
        api_key = os.environ.get("GROQ_API_KEY", "")
        api_url = os.environ.get("GROQ_ASR_URL", "https://api.groq.com/openai/v1/audio/transcriptions")
        model = os.environ.get("GROQ_ASR_MODEL", "whisper-large-v3-turbo")
        max_mb = int(os.environ.get("ASR_MAX_FILE_MB", "25"))
        if not url.startswith(("https://", "http://")):
            raise ValueError("A valid public HTTPS/HTTP media URL is required.")
        if not api_key:
            raise RuntimeError("GROQ_API_KEY is not configured.")
        ffmpeg_location = os.environ.get("FFMPEG_LOCATION") or None

        with tempfile.TemporaryDirectory(prefix="videotranscript-") as temp:
            work = Path(temp)
            options = {
                "format": "bestaudio/best",
                "outtmpl": str(work / "source.%(ext)s"),
                "noplaylist": True,
                "quiet": True,
                "no_warnings": True,
                "ffmpeg_location": ffmpeg_location,
                "postprocessors": [{
                    "key": "FFmpegExtractAudio",
                    "preferredcodec": "mp3",
                    "preferredquality": "48",
                }],
            }
            with YoutubeDL(options) as ydl:
                info = ydl.extract_info(url, download=True)
                prepared = Path(ydl.prepare_filename(info))
                audio_file = prepared.with_suffix(".mp3")
                if not audio_file.exists():
                    candidates = list(work.glob("*.mp3"))
                    if not candidates:
                        raise RuntimeError("yt-dlp/FFmpeg did not produce an MP3 audio file.")
                    audio_file = candidates[0]
                duration = info.get("duration")

            size_mb = audio_file.stat().st_size / (1024 * 1024)
            if size_mb > max_mb:
                raise RuntimeError(
                    f"Extracted audio is {size_mb:.1f} MB; configured provider limit is {max_mb} MB. "
                    "Use a larger provider limit or a shorter/lower-bitrate source."
                )

            data = {"model": model, "response_format": "verbose_json", "temperature": "0"}
            if language and language != "auto":
                data["language"] = language
            with audio_file.open("rb") as audio:
                response = requests.post(
                    api_url,
                    headers={"Authorization": f"Bearer {api_key}"},
                    data=data,
                    files={"file": (audio_file.name, audio, "audio/mpeg")},
                    timeout=(30, 900),
                )
            if response.status_code < 200 or response.status_code >= 300:
                raise RuntimeError(f"Groq transcription HTTP {response.status_code}: {response.text[:400]}")
            result = response.json()
            result["duration"] = result.get("duration") or duration
            print(json.dumps({
                "text": result.get("text", ""),
                "segments": result.get("segments", []),
                "raw": result,
                "provider": "local_ytdlp_groq",
                "model": model,
            }, ensure_ascii=False))
        return 0
    except Exception as exc:
        print(str(exc), file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
