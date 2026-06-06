<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class CnnService
{
    private string $pythonPath;
    private string $scriptPath;

    public function __construct()
    {
        $this->pythonPath = env('PYTHON_PATH', 'python');
        $this->scriptPath = base_path('cnn/predict.py');
    }

    /**
     * Run CNN prediction on an uploaded image.
     *
     * @param  string  $absoluteImagePath
     * @return array{status: string, confidence: float, error: string|null}
     */
    public function predict(string $absoluteImagePath): array
    {
        // Verify file exists
        if (!file_exists($absoluteImagePath)) {
            \Log::error('CNN File not found', ['path' => $absoluteImagePath]);
            return [
                'status'     => 'invalid',
                'confidence' => 0.0,
                'error'      => 'Image file not found',
            ];
        }

        // Use cmd.exe with array format
        $cmdPath = base_path('cnn/predict.bat');

        $process = new Process([
            'cmd',
            '/c',
            $cmdPath,
            $absoluteImagePath,
        ]);

        $process->setTimeout(120);

        // Set environment variables
        $env = [
            'HOME' => getenv('USERPROFILE'),
            'USERPROFILE' => getenv('USERPROFILE'),
            'TF_CPP_MIN_LOG_LEVEL' => '3',
            'TF_ENABLE_ONEDNN_OPTS' => '0',
            'CUDA_VISIBLE_DEVICES' => '',
            'OMP_NUM_THREADS' => '1',
            'TEMP' => sys_get_temp_dir(),
        ];
        $process->setEnv($env);

        try {
            $process->run();

            $output = trim($process->getOutput());

            \Log::info('CNN Result', [
                'exit_code' => $process->getExitCode(),
                'output_length' => strlen($output),
            ]);

            // Check if successful and has valid JSON output
            if ($process->getExitCode() === 0 && !empty($output)) {
                $result = json_decode($output, true);

                if (is_array($result) && isset($result['status'])) {
                    \Log::info('CNN Success', [
                        'status' => $result['status'],
                        'confidence' => $result['confidence'],
                    ]);

                    return [
                        'status'     => $result['status'],
                        'confidence' => (float) ($result['confidence'] ?? 0.0),
                        'error'      => null,
                    ];
                }
            }

            \Log::error('CNN Failed', [
                'exit_code' => $process->getExitCode(),
                'output' => substr($output, 0, 500),
                'stderr' => substr($process->getErrorOutput(), 0, 500),
            ]);

            return [
                'status'     => 'invalid',
                'confidence' => 0.0,
                'error'      => 'CNN prediction failed',
            ];

        } catch (\Exception $e) {
            \Log::error('CNN Exception', ['error' => $e->getMessage()]);
            return [
                'status'     => 'invalid',
                'confidence' => 0.0,
                'error'      => $e->getMessage(),
            ];
        }
    }
}
