<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionCollection;
use App\Http\Resources\TransactionResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    use ApiResponse;

    public function encrypt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendFailed($validator->errors()->all(), 422);
        }

        $response = $this->encryptData($request->only('title', 'content'), auth()->user()->api_key);
        return $this->sendSuccess('success', $response);
    }

    private function encryptData(array $data, string $apiKey)
    {
        $data = json_encode($data); // Convert data to JSON string

        // Generate an encryption key based on the API key
        $encryptionKey = hash('sha256', $apiKey, true);

        // Encrypt the data
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, 0, $iv);

        // Encode the IV with the encrypted data
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendFailed($validator->errors()->all(), 422);
        }

        $response = $this->decryptData($request->data, auth()->user()->api_key);
        if ($response) {
            return $this->sendSuccess('success', $response);
        } else {
            return $this->sendFailed('data not found', 400);
        }
    }

    private function decryptData(string $encrypted, string $apiKey)
    {
        // Generate an encryption key based on the API key
        $encryptionKey = hash('sha256', $apiKey, true);

        // Decode the encrypted string
        $encrypted = base64_decode($encrypted);

        // Extract the IV from the encrypted data
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($encrypted, 0, $ivLength);
        $encrypted = substr($encrypted, $ivLength);

        // Decrypt the data
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $encryptionKey, 0, $iv);

        return json_decode($decrypted, true); // Convert JSON string to array
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendFailed($validator->errors()->all(), 422);
        }

        $response = $this->decryptData($request->data, auth()->user()->api_key);
        if (!$response) {
            return $this->sendFailed('data not found', 400);
        }

        // Validate the data before storing
        $validator = Validator::make($response, [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendFailed($validator->errors()->all(), 422);
        }

        $request->user()->transactions()->create($response);
        return $this->sendSuccess('Transaction save success');
    }

    public function index(Request $request)
    {
        $transactions = $request->user()->transactions;
        if ($transactions->isEmpty()) {
            return $this->sendFailed('data not found', 400);
        }

        $data = new TransactionCollection($transactions);
        $response = $this->encryptData($data->resolve(), auth()->user()->api_key);
        return $this->sendSuccess('success', $response);
    }

    public function show(Request $request, $id)
    {
        $transaction = $request->user()->transactions()->findOrFail($id);

        $data = new TransactionResource($transaction);
        $response = $this->encryptData($data->resolve(), auth()->user()->api_key);
        return $this->sendSuccess('success', $response);
    }
}
