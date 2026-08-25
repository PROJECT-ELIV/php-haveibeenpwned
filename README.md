# HIBP Client

A minimal PHP client for the [Have I Been Pwned](https://haveibeenpwned.com/) Pwned Passwords API.

It checks whether a password has appeared in known data breaches using the range (k-anonymity) endpoint — only the first 5 characters of the SHA-1 hash are ever sent to the API, so the password itself never leaves your server.

## Requirements

- PHP 8.0+
- `ext-curl`

## Installation

Drop `HIBP.php` into your project and require it:

```php
require_once __DIR__ . '/HIBP.php';
```

## Usage

```php
$HIBP = new HIBP();
$Result = $HIBP->CheckLeakPassword('P@ssw0rd');

if ($Result['Result'] !== true) {
    // Request failed
    echo $Result['Message'];
    exit;
}

if ($Result['Data']['IsLeaked']) {
    echo "Compromised {$Result['Data']['LeakCount']} times. Choose another password.";
} else {
    echo "This password has not been found in any known breach.";
}
```

## Response Format

Every method returns an array with the same shape:

```php
[
    'Result'  => bool,   // true on success, false on failure
    'Message' => string, // human-readable description
    'Code'    => string, // machine-readable status code
    'Data'    => array   // payload (may be empty)
]
```

On a successful check, `Data` contains:

| Key | Type | Description |
| --- | --- | --- |
| `IsLeaked` | `bool` | Whether the password was found in a breach |
| `LeakCount` | `int` | Number of times it appeared (`0` if not leaked) |

## Status Codes

| Code | Meaning |
| --- | --- |
| `PASSWORD_NOT_LEAKED` | Password was not found in any known breach |
| `PASSWORD_LEAKED` | Password was found in one or more breaches |
| `INVALID_PASSWORD` | The password could not be hashed |
| `CURL_REQUEST_FAILED` | The HTTP request could not be completed |
| `HTTP_ERROR_{code}` | The API returned a non-2xx status |

> Note: `PASSWORD_LEAKED` still returns `Result => true` — the request succeeded. Always branch on `Data['IsLeaked']`, not on `Result`, to decide whether to accept the password.

## Methods

### `CheckLeakPassword(string $Password): array`

Hashes the password with SHA-1, queries the range endpoint with the first 5 characters, and compares the remaining 35 characters locally against the returned list.

### `Fetch(string $Method, string $Path, array $Data = []): array`

Low-level HTTP wrapper around the API. `GET` requests append `$Data` as a query string; other methods send it as a JSON body.

## Privacy

The full password and its full hash are never transmitted. The API receives only a 5-character hash prefix and responds with every suffix sharing that prefix, so it cannot tell which one you were asking about.

## License

MIT

<p align="center">
  Made with 🩵 by <a href="https://eliv.digital/">PROJECT ELIV</a> in 🇰🇷
</p>
