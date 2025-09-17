# TRMNL Pipeline PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bnussbau/trmnl-pipeline-php.svg?style=flat-square)](https://packagist.org/packages/bnussbau/trmnl-pipeline-php)
[![Tests](https://img.shields.io/github/actions/workflow/status/bnussbau/trmnl-pipeline-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bnussbau/trmnl-pipeline-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/bnussbau/trmnl-pipeline-php.svg?style=flat-square)](https://packagist.org/packages/bnussbau/trmnl-pipeline-php)

TRMNL Pipeline PHP provides a streamlined API, based on the pipeline pattern, for converting HTML content into optimized images for a wide range of e-ink devices. The image processing pipeline includes grayscale conversion, color quantization, and device-specific optimizations.

<img width="800" height="480" alt="image" src="https://github.com/user-attachments/assets/e84fc752-552e-4cb9-a1c0-aa2596176db7" />


## Features

- **Browser Rendering**: HTML to image conversion using Spatie Browsershot
- **Image Processing**: Advanced image manipulation using ImageMagick
- **TRMNL Models API**: Automatic support for >=12 different e-ink device models.

## Requirements

- PHP 8.2 or higher
- Imagick extension
- Spatie Browsershot (requires Node.js and Puppeteer -> see [Browsershot Requirements](https://spatie.be/docs/browsershot/v4/requirements))

## Installation
You can install the package via composer:

```bash
composer require bnussbau/trmnl-pipeline-php
```

## Usage

### With Model Configuration
Render HTML and convert to image compatible with the TRMNL OG model.

```php
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;
use Bnussbau\TrmnlPipeline\Stages\ImageStage;
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;

$html = file_get_contents('./tests/assets/framework2_og.html');

$image = new TrmnlPipeline()
    ->model(Model::OG)
    ->pipe(new BrowserStage()
        ->html($html))
    ->pipe(new ImageStage())
    ->process();

echo "Generated image: $image";
```
Generates PNG 800x480 8-bit Grayscale Gray 4c

### Image Processing Only

```php
use Bnussbau\TrmnlPipeline\Stages\ImageStage;
use Bnussbau\TrmnlPipeline\Model;

$imageStage = new ImageStage();
$imageStage->configureFromModel(Model::OG_BMP);

$result = $imageStage('./tests/assets/browsershot_og_1bit.png');
echo "Processed image: $result";
```

Generates BMP3 800x480 1-bit sRGB 2c

### Manual Configuration

```php
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;
use Bnussbau\TrmnlPipeline\Stages\ImageStage;
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;

$html = file_get_contents('./tests/assets/framework2_og.html');

$image = new TrmnlPipeline()
    ->pipe(new BrowserStage()
        ->html($html))
    ->pipe(new ImageStage()
        ->format('png')
        ->width(800)
        ->height(600)
        ->rotation(90)
        ->colors(256)
        ->bitDepth(8))
    ->process();

echo "Generated image: $image";
```

## API Reference

### Pipeline

The main pipeline class that orchestrates the processing stages.

```php
$pipeline = new Pipeline();
$pipeline->model(Model::OG_PNG); // Set model for automatic configuration
$pipeline->pipe(new BrowserStage()); // Add browser stage
$pipeline->pipe(new ImageStage()); // Add image stage
$result = $pipeline->process($payload); // Process payload
```

### BrowserStage

Converts HTML to PNG images using Spatie Browsershot.

```php
$browserStage = new BrowserStage();
$browserStage
    ->html('<html><body>Content</body></html>')
    ->width(800)
    ->height(480)
    ->setBrowsershotOption('addStyleTag', json_encode(['content' => 'body{ color: red; }']));

$result = $browserStage(null);
```

### ImageStage

Processes images for e-ink display compatibility.

```php
$imageStage = new ImageStage();
$imageStage
    ->format('png')
    ->width(800)
    ->height(480)
    ->offsetX(0)
    ->offsetY(0)
    ->rotation(0)
    ->colors(2)
    ->bitDepth(1)
    ->outputPath('/path/to/output.png');

$result = $imageStage('/path/to/input.png');
```

### Model

Access device model configurations.

```php
$model = Model::OG_PNG;
$data = $model->getData();

echo $model->getLabel(); // "TRMNL OG (1-bit)"
echo $model->getWidth(); // 800
echo $model->getHeight(); // 480
echo $model->getColors(); // 2
echo $model->getBitDepth(); // 1
```

## Development

### Running Tests

```bash
composer test
composer test-coverage
```

### Code Quality

```bash
composer format
composer analyse
composer rector
```

## License

MIT License. See LICENSE file for details.

## Contributing

1. Create an issue to discuss your idea
2. Fork the repository
3. Create a feature branch
4. Make your changes
5. Add tests to maintain coverage
6. Run the test suite
7. Submit a pull request
