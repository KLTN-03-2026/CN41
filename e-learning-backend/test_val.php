<?php

$validator = validator(
    [
        'source' => 'chapter',
        'count' => '10',
        'max_attempts' => '3',
    ],
    [
        'source' => 'required|in:upload,chapter',
        'count' => 'nullable|integer|min:1|max:10',
        'file' => 'required_if:source,upload|nullable|file|mimes:pdf|max:20480',
        'custom_prompt' => 'nullable|string|max:500',
        'max_attempts' => 'nullable|integer|min:1|max:10',
        'time_limit' => 'nullable|integer|min:1',
    ]
);
if ($validator->fails()) {
    echo json_encode($validator->errors()->toArray(), JSON_PRETTY_PRINT);
} else {
    echo "Validation passed\n";
}
