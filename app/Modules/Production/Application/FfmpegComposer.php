<?php

namespace App\Modules\Production\Application;

use RuntimeException;
use Symfony\Component\Process\Process;

final class FfmpegComposer
{
    public function composeSlideshow(array $images, array $durationsMs, string $voiceInput, string $captionsInput, string $output): void
    {
        if (count($images) !== count($durationsMs) || $images === []) {
            throw new RuntimeException('Every slideshow image requires a duration.');
        }
        $manifest = dirname($output).'/slideshow-'.bin2hex(random_bytes(6)).'.txt';
        $lines = [];
        foreach ($images as $index => $image) {
            if (! is_file($image)) {
                throw new RuntimeException("Generated scene is missing: {$image}");
            }
            $escaped = str_replace("'", "'\\''", $image);
            $lines[] = "file '{$escaped}'";
            $lines[] = 'duration '.max(0.1, $durationsMs[$index] / 1000);
        }
        $lines[] = "file '".str_replace("'", "'\\''", end($images))."'";
        file_put_contents($manifest, implode(PHP_EOL, $lines));

        $filter = sprintf("scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,subtitles='%s':force_style='Alignment=2,MarginV=240,FontSize=18,Outline=3'", str_replace(['\\', "'", ':'], ['\\\\', "\\'", '\\:'], $captionsInput));
        $process = new Process([config('media_providers.ffmpeg_binary', 'ffmpeg'), '-y', '-f', 'concat', '-safe', '0', '-i', $manifest, '-i', $voiceInput, '-vf', $filter, '-map', '0:v:0', '-map', '1:a:0', '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-r', '30', '-c:a', 'aac', '-ar', '48000', '-b:a', '128k', '-movflags', '+faststart', '-shortest', $output]);
        $process->setTimeout(1200);
        try {
            $process->mustRun();
        } finally {
            @unlink($manifest);
        }
    }

    public function compose(string $videoInput, string $voiceInput, string $captionsInput, string $output): void
    {
        foreach ([$videoInput, $voiceInput, $captionsInput] as $input) {
            if (! is_file($input)) {
                throw new RuntimeException("Required composition input does not exist: {$input}");
            }
        }

        $filter = sprintf(
            "scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,subtitles='%s':force_style='Alignment=2,MarginV=240,FontSize=18,Outline=3'",
            str_replace(['\\', "'", ':'], ['\\\\', "\\'", '\\:'], $captionsInput),
        );

        $process = new Process([
            config('media_providers.ffmpeg_binary', 'ffmpeg'), '-y',
            '-i', $videoInput,
            '-i', $voiceInput,
            '-filter_complex', '[0:v]'.$filter.'[v]',
            '-map', '[v]',
            '-map', '1:a:0',
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            '-r', '30',
            '-c:a', 'aac',
            '-ar', '48000',
            '-b:a', '128k',
            '-movflags', '+faststart',
            '-shortest',
            $output,
        ]);
        $process->setTimeout(1200);
        $process->mustRun();
    }
}
