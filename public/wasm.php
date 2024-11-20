<html>
<head>
    <script async type = "text/javascript" src = "https://cdn.jsdelivr.net/npm/php-wasm/php-tags.mjs"></script>
    <script id = "input" src = "/test-input.json" type = "text/json"></script>
    <script type = "text/php" src = "/test.php" data-stdin = "#input" data-stdout = "#output" data-stderr = "#error"></script>
</head>
<body>
<div id = "output"></div>
<div id = "error"></div>
</body>
</html>