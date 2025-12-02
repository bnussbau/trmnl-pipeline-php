# TRMNL Pipeline PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bnussbau/trmnl-pipeline-php.svg?style=flat-square)](https://packagist.org/packages/bnussbau/trmnl-pipeline-php)
[![License](https://img.shields.io/badge/License%20-MIT-blue?style=flat-square)](https://packagist.org/packages/bnussbau/trmnl-pipeline-php)
[![Tests](https://img.shields.io/github/actions/workflow/status/bnussbau/trmnl-pipeline-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bnussbau/trmnl-pipeline-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/bnussbau/trmnl-pipeline-php.svg?style=flat-square)](https://packagist.org/packages/bnussbau/trmnl-pipeline-php)

TRMNL Pipeline PHP provides a streamlined API, based on the pipeline pattern, for converting HTML content (or images) into optimized images for e-ink devices supported by the [TRMNL Models API](https://usetrmnl.com/api/models). The image processing pipeline includes features like scaling, rotation, grayscale conversion, color quantization, and format-specific optimizations. This package is used in [usetrmnl/byos_laravel](https://github.com/usetrmnl/byos_laravel).

Command line wrapper for this package: [trmnl-pipeline-cmd](https://github.com/bnussbau/trmnl-pipeline-cmd)

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

### Setting the Browser Timezone (optional)

You can control the timezone used by the headless browser when rendering your HTML by calling `timezone()` on `BrowserStage` with any valid PHP timezone identifier (e.g., `UTC`, `America/New_York`, `Europe/Berlin`).

Notes:
- The timezone is only applied when you explicitly call `timezone()`. If you don’t explicitly set the timezone, the browser will use the system timezone.
- This can be helpful when your HTML or scripts render time/date-dependent content.

Example:

```php
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;

$image = (new \Bnussbau\TrmnlPipeline\TrmnlPipeline())
    ->pipe((new BrowserStage())
        ->timezone('America/New_York')
        ->html('<html><body><script>document.write(new Date().toString())</script></body></html>'))
    ->pipe(new \Bnussbau\TrmnlPipeline\Stages\ImageStage())
    ->process();
```

### Browser Rendering on AWS Lambda

You can use different Browsershot implementations (like BrowsershotLambda) by passing an instance to the BrowserStage.
See installation instructions and requirments for [stefanzweifel/sidecar-browsershot](https://github.com/stefanzweifel/sidecar-browsershot).

```php
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;
use Bnussbau\TrmnlPipeline\Stages\ImageStage;
use Wnx\SidecarBrowsershot\BrowsershotLambda;

$html = file_get_contents('./tests/assets/framework2_og.html');

// Create your custom Browsershot instance (e.g., BrowsershotLambda)
$browsershotLambda = new BrowsershotLambda();

$image = new TrmnlPipeline()
    ->model(Model::OG)
    ->pipe(new BrowserStage($browsershotLambda)
        ->html($html))
    ->pipe(new ImageStage())
    ->process();

echo "Generated image: $image";
```

This allows you to use BrowsershotLambda or any other Browsershot implementation that extends `Spatie\Browsershot\Browsershot`.

### Testing with Fake Mode

You can use the `fake()` method to prevent actual Browsershot and Imagick operations:

```php
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;
use Bnussbau\TrmnlPipeline\Stages\ImageStage;

// Enable fake mode for testing
TrmnlPipeline::fake();

$html = '<html><body>Test Content</body></html>';

$result = (new TrmnlPipeline())
    ->model(Model::OG)
    ->pipe(new BrowserStage()->html($html))
    ->pipe(new ImageStage())
    ->process();

echo "Mock image generated: $result";

// Disable fake mode when done
TrmnlPipeline::restore();
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
    ->useDefaultDimensions() // force 800x480 e.g. in combination with Model to upscale image
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

#### Dithering

Recommended for photos but not for images containing mostly text, where it can make edges and letters appear rough or unclear. Dithering converts a grayscale photo into only black and white pixels by using patterns or noise to simulate intermediate shades, creating the illusion of continuous tones through spatial averaging.

```php
use Bnussbau\TrmnlPipeline\Stages\ImageStage;

(new ImageStage())
    ->dither()
    ->colors(2)
    ->bitDepth(1);
```

#### Color Support

The pipeline supports color images via palettes defined in `palettes.json`. Models can specify one or more palette IDs, and the first palette with a `colors` array will be automatically applied. Color palettes use RGB colorspace for quantization and support dithering.

**Example 1: Using Model Preset with Color Palette**

```php
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;
use Bnussbau\TrmnlPipeline\Stages\ImageStage;

$html = file_get_contents('./tests/assets/color_6a_test.html');

// Inky Impression 13.3 model has color-6a palette (6 colors: red, green, blue, yellow, black, white)
$image = new TrmnlPipeline()
    ->model(Model::INKY_IMPRESSION_13_3)
    ->pipe(new BrowserStage()
        ->html($html))
    ->pipe(new ImageStage())
    ->process();

echo "Generated color image: $image";
```

**Example 2: Defining Color Palette as Array**

```php
use Bnussbau\TrmnlPipeline\Stages\ImageStage;

// Define custom color palette (6 colors)
$colorPalette = [
    '#FF0000', // Red
    '#00FF00', // Green
    '#0000FF', // Blue
    '#FFFF00', // Yellow
    '#000000', // Black
    '#FFFFFF', // White
];

$imageStage = new ImageStage();
$imageStage
    ->format('png')
    ->colormap($colorPalette)
    ->dither(true); // Dithering works with color palettes (optional; only use for images)

$result = $imageStage('/path/to/input.png');
echo "Processed color image: $result";
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
