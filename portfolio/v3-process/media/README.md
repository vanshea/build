# Replacing a process film

Each project has five assets here. Its entry in `../../data/projects.json` names the 720 px and 1280 px MP4 and WebM files and the poster. The mobile player selects the smaller source at viewport widths up to 720 CSS pixels. Each supplied clip lasts eight seconds, loops silently, and shows Sketch, Wireframe, Iteration, and Final for two seconds each.

## Convert a screen recording

Run these commands in this media directory with FFmpeg on your PATH. Choose an eight-second segment that reads well in a loop. Set the three variables to your recording, project ID, and start time. The filter preserves the artwork's proportions inside the player’s 16:10 frame. Existing files with these output names will prompt before replacement.

```sh
input="/path/to/recording.mov"
slug="common-ground"
start="00:00:00"

for width in 720 1280
do
  if [ "$width" = 720 ]; then height=450; rate=480k; else height=800; rate=650k; fi
  filter="fps=24,scale=${width}:${height}:force_original_aspect_ratio=decrease:force_divisible_by=2:flags=lanczos,pad=${width}:${height}:(ow-iw)/2:(oh-ih)/2,setsar=1,format=yuv420p"
  ffmpeg -ss "$start" -i "$input" -t 8 -map 0:v:0 -an -vf "$filter" \
    -c:v libx264 -preset slow -b:v "$rate" -maxrate 700k -bufsize 1000k \
    -movflags +faststart "${slug}-${width}.mp4"
  ffmpeg -ss "$start" -i "$input" -t 8 -map 0:v:0 -an -vf "$filter" \
    -c:v libvpx-vp9 -row-mt 1 -cpu-used 3 -crf 34 -b:v "$rate" \
    -maxrate 700k -bufsize 1000k "${slug}-${width}.webm"
done

ffmpeg -i "${slug}-1280.mp4" -frames:v 1 -vf scale=960:600 -q:v 3 "${slug}-poster.jpg"
wc -c "${slug}-720.mp4" "${slug}-720.webm" "${slug}-1280.mp4" "${slug}-1280.webm"
```

Aim for less than 800,000 bytes per video. The ceiling is 1,500,000 bytes. If a recording exceeds the budget, lower the bitrate or shorten it to six seconds and inspect small text again. The poster should communicate the first stage on its own. Update the project text and stage names in the JSON when the recording changes.

## Reproduce the four-frame placeholder encode

Provide four 1280 × 800 PNGs named `0.png` through `3.png` in stage order. Set `frames` to their directory and `slug` to the project ID. These are the input cadence, filters and codec settings used for the supplied clips. The final-frame hold preserves the last two seconds when resampling to 24 fps.

```sh
frames="/path/to/four-frames"
slug="common-ground"
for width in 720 1280
do
  filter="scale=${width}:-2:flags=lanczos,tpad=stop_mode=clone:stop_duration=2,fps=24,format=yuv420p"
  ffmpeg -framerate 1/2 -start_number 0 -i "$frames/%d.png" -t 8 -vf "$filter" \
    -an -threads 2 -c:v libx264 -preset veryfast -crf 23 -maxrate 700k \
    -bufsize 1000k -movflags +faststart "${slug}-${width}.mp4"
  ffmpeg -framerate 1/2 -start_number 0 -i "$frames/%d.png" -t 8 -vf "$filter" \
    -an -threads 2 -c:v libvpx-vp9 -row-mt 1 -cpu-used 5 -crf 36 -b:v 0 "${slug}-${width}.webm"
done
```

## Supplied file sizes in decimal KB

| Project | Mobile MP4 | Mobile WebM | Desktop MP4 | Desktop WebM | Poster JPEG |
| --- | ---: | ---: | ---: | ---: | ---: |
| Common Ground | 35.5 | 57.2 | 64.9 | 110.0 | 29.0 |
| Relay | 21.0 | 35.8 | 33.8 | 59.6 | 22.9 |
| Daybreak | 33.8 | 57.3 | 64.8 | 117.1 | 27.7 |
| Field Notes | 35.0 | 55.7 | 64.4 | 108.4 | 27.7 |
| Signal Room | 21.7 | 37.4 | 34.9 | 62.5 | 24.5 |
| Waypoint | 33.4 | 55.6 | 63.6 | 112.9 | 27.2 |
