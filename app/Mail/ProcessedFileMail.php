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

    public function __construct($filePath, $user)
    {
        $this->filePath = $filePath;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Verarbeitete Datei')
                    ->view('processed_file')
                    ->attach($this->filePath, [
                        'as' => 'Verarbeitete_Dateien_Daten.xlsx',
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
    }
}
