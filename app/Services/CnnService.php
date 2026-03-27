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
        $process = new Process([
            $this->pythonPath,
            $this->scriptPath,
            $absoluteImagePath,
        ]);

        $process->setTimeout(60);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                return [
                    'status'     => 'invalid',
                    'confidence' => 0.0,
                    'error'      => $process->getErrorOutput(),
                ];
            }

            $output = trim($process->getOutput());
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($result['status'])) {
                return [
                    'status'     => 'invalid',
                    'confidence' => 0.0,
                    'error'      => 'Invalid JSON from CNN script: ' . $output,
                ];
            }

            return [
                'status'     => $result['status'],
                'confidence' => (float) ($result['confidence'] ?? 0.0),
                'error'      => null,
            ];
        } catch (\Exception $e) {
            return [
                'status'     => 'invalid',
                'confidence' => 0.0,
                'error'      => $e->getMessage(),
            ];
        }
    }
}
