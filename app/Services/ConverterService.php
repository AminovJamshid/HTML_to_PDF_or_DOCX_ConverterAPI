<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ConverterService
{
    /**
     * HTML ni PDF yoki DOCX ga o'giradi va saqlaydi
     */
    public function convertAndSave(string $html, string $format, string $fileName): array
    {
        // Fayl yo'lini yaratish: uploads/documents/{YIL}/{OY}/{KUN}/
        $date = Carbon::now();
        $directory = 'uploads/documents/' . $date->format('Y/m/d');
        
        // Unique fayl nomi yaratish
        $uniqueFileName = $this->generateUniqueFileName($fileName, $format);
        $fullPath = $directory . '/' . $uniqueFileName;
        
        // Formatga qarab konvertatsiya qilish
        if ($format === 'pdf') {
            $fileContent = $this->convertToPdf($html);
        } else {
            $fileContent = $this->convertToDocx($html);
        }
        
        // Faylni saqlash (storage/app/public/ ga)
        Storage::disk('public')->put($fullPath, $fileContent);
        
        // Fayl ma'lumotlarini qaytarish
        return [
            'url' => Storage::disk('public')->url($fullPath),
            'file_name' => $uniqueFileName,
            'file_path' => $fullPath,
            'file_size' => Storage::disk('public')->size($fullPath),
            'format' => $format,
            'created_at' => $date->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * HTML ni PDF ga o'girish
     */
    private function convertToPdf(string $html): string
    {
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->output();
    }
    
    /**
     * HTML ni DOCX ga o'girish
     */
    private function convertToDocx(string $html): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        // HTML ni Word formatiga o'girish
        Html::addHtml($section, $html, false, false);
        
        // Temporary fayl yaratish
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);
        
        $content = file_get_contents($tempFile);
        unlink($tempFile); // Temporary faylni o'chirish
        
        return $content;
    }
    
    /**
     * Unique fayl nomi yaratish
     */
    private function generateUniqueFileName(string $baseName, string $format): string
    {
        // Maxsus belgilarni tozalash
        $baseName = preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName);
        
        // Timestamp qo'shish
        $timestamp = time();
        
        return $baseName . '_' . $timestamp . '.' . $format;
    }
}