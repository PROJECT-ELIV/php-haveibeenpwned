<?php

class HIBP
{

    private string $API_BaseURL = "https://api.pwnedpasswords.com";

    public function Return(bool $Result, string $Message, string $Code, array $Data = [])
    {
        return get_defined_vars();
    }

    public function Fetch(string $Method, string $Path, array $Data = []): array
    {
        $CurlOptions = [
            CURLOPT_CUSTOMREQUEST => $Method,
            CURLOPT_URL => $this->API_BaseURL . $Path,
            CURLOPT_HTTPHEADER => ["User-Agent: PROJECT-ELIV HIBP-Client/1.0"],
            CURLOPT_RETURNTRANSFER => true
        ];

        if ($Method === "GET") $CurlOptions[CURLOPT_URL] .= "?" . http_build_query($Data);
        else $CurlOptions[CURLOPT_POSTFIELDS] = json_encode($Data);

        $Curl = curl_init();
        curl_setopt_array($Curl, $CurlOptions);
        $Result = curl_exec($Curl);
        curl_close($Curl);

        if ($Result === false) {
            return $this->Return(false, "An error occurred during cURL request (" . curl_error($Curl) . ")", "CURL_REQUEST_FAILED");
        }

        $HttpCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
        if ($HttpCode < 200 || $HttpCode >= 300) {
            return $this->Return(false, "HTTP error occurred ({$HttpCode})", "HTTP_ERROR_{$HttpCode}");
        }

        return $this->Return(true, "Request completed successfully", "OK", ["Response" => $Result]);
    }

    public function CheckLeakPassword(string $Password)
    {
        $Sha1 = sha1($Password);
        if (empty($Sha1) || $Sha1 === false || strlen($Sha1) !== 40) return $this->Return(false, "Invalid password", "INVALID_PASSWORD");

        $Result = $this->Fetch("GET", "/range/" . substr($Sha1, 0, 5));
        if ($Result['Result'] !== true) return $Result;

        $LeakedHashes = explode("\n", $Result['Data']['Response']);

        $EndedHash = substr($Sha1, 5);
        foreach ($LeakedHashes as $HashLine) {
            $Parts = explode(':', trim($HashLine));
            if (strtoupper($Parts[0]) === strtoupper($EndedHash)) {
                return $this->Return(true, "This password cannot be used because it has been compromised " . number_format($Parts[1]) . " times.", "PASSWORD_LEAKED", [
                    "IsLeaked" => true,
                    "LeakCount" => $Parts[1]
                ]);
            }
        }

        return $this->Return(true, "This password is not compromised", "PASSWORD_NOT_LEAKED", [
            "IsLeaked" => false,
            "LeakCount" => 0
        ]);
    }
}
