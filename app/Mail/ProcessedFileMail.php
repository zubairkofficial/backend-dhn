<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcessedFileMail extends Mailable
{
    use Queueable, SerializesModels;

    public $filePath;
    public $user;
    public $attachmentName;

    public function __construct($filePath, $user, $attachmentName = null)
    {
        $this->filePath       = $filePath;
        $this->user           = $user;
        $this->attachmentName = $attachmentName ?? 'Verarbeitete_Dateien_Daten.xlsx';
    }

    public function build()
    {
        return $this->subject('Verarbeitete Datei')
            ->view('processed_file')
            ->attach($this->filePath, [
                'as'   => $this->attachmentName,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
