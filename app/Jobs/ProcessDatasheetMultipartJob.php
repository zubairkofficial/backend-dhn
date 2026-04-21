<?php

namespace App\Jobs;

use App\Models\CloneDataProcess;
use App\Services\ExternalProcessingClient;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessDatasheetMultipartJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function __construct(
        public string $endpointKey,
        public string $storageRelativePath,
        public string $originalFileName,
        public int $userId,
        public string $modelClass,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(ExternalProcessingClient $client): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $disk = Storage::disk('local');
        $fullPath = $disk->path($this->storageRelativePath);

        if (! is_readable($fullPath)) {
            Log::error('processing.job_missing_file', ['path' => $this->storageRelativePath]);

            return;
        }

        try {
            $response = $client->postMultipart(
                $this->endpointKey,
                $fullPath,
                $this->originalFileName,
                [],
                ['model' => $this->modelClass, 'user_id' => $this->userId]
            );
        } catch (Throwable $e) {
            $this->persistError($e->getMessage());
            @unlink($fullPath);

            return;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) {
            $responseData = json_decode($response->getBody(), true);
            $attrs = [
                'file_name' => $this->originalFileName,
                'data' => base64_encode(json_encode($responseData)),
                'user_id' => $this->userId,
            ];
            $this->applyStatusSuccess($attrs);
            $this->modelClass::create($attrs);
        } else {
            $errorMessage = 'Unexpected status code: '.$statusCode;
            $attrs = [
                'file_name' => $this->originalFileName,
                'data' => $this->emptyDataForError(),
                'user_id' => $this->userId,
            ];
            $this->applyStatusError($attrs, $errorMessage);
            $this->modelClass::create($attrs);
        }

        @unlink($fullPath);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function applyStatusSuccess(array &$attrs): void
    {
        $model = new $this->modelClass;
        if (in_array('status', $model->getFillable(), true)) {
            $attrs['status'] = 'success';
        }
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function applyStatusError(array &$attrs, string $message): void
    {
        $model = new $this->modelClass;
        if (in_array('status', $model->getFillable(), true)) {
            $attrs['status'] = 'error';
        }
        if (in_array('error_message', $model->getFillable(), true)) {
            $attrs['error_message'] = $message;
        }
    }

    protected function emptyDataForError(): ?string
    {
        if ($this->modelClass === CloneDataProcess::class) {
            return base64_encode(json_encode([]));
        }

        return null;
    }

    protected function persistError(string $message): void
    {
        $attrs = [
            'file_name' => $this->originalFileName,
            'data' => $this->emptyDataForError(),
            'user_id' => $this->userId,
        ];
        $this->applyStatusError($attrs, $message);
        $this->modelClass::create($attrs);
    }
}
