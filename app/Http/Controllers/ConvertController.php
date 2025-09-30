<?php

namespace App\Http\Controllers;

use App\Services\ConverterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ConvertController extends Controller
{
    /**
     * HTML ni PDF yoki DOCX ga konvertatsiya qilish
     */
    public function convert(Request $request, ConverterService $service)
    {
        // Validatsiya
        $validator = Validator::make($request->all(), [
            'format'    => ['required', Rule::in(['pdf', 'docx'])],
            'html'      => 'required|string|max:204800', // ~200KB
            'file_name' => 'nullable|string|max:100',
        ], [
            'format.required' => 'Format majburiy (pdf yoki docx)',
            'format.in' => 'Format faqat pdf yoki docx bo\'lishi kerak',
            'html.required' => 'HTML kodi majburiy',
            'html.max' => 'HTML kodi juda katta (max 200KB)',
            'file_name.max' => 'Fayl nomi juda uzun (max 100 belgi)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Konvertatsiya qilish
            $result = $service->convertAndSave(
                $data['html'],
                $data['format'],
                $data['file_name'] ?? 'document'
            );

            return response()->json([
                'success' => true,
                'data'    => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Konvertatsiyada xatolik yuz berdi',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}