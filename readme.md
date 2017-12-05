# PHP Sprite Atlas Generator

Combine images into one sprite atlas with CSS rules for positioning.
Each image will be scaled to fit `cell_width x cell_height` size
and placed justified in atlas with `cell_padding`.

## Usage

Prepare data in format `[name => full file path]`

```php
$paths = [
    'stark'   => '/images/arya-one-luv.jpg',
    'mormont' => '/images/mormont.png',
    'bolton'  => '/images/bolton.png',
    'arryn'   => '/images/arryn.png',
];
```

Setup generator and perform:

```php
$g = new SpriteAtlasGenerator([
	// full path to result css sprite atlas
	'out_css' => $_SERVER['DOCUMENT_ROOT'].'/assets/css/westeros.css',
	// full path to result atlas image
	'out_image' => $_SERVER['DOCUMENT_ROOT'].'/assets/img/westeros.atlas.jpg',
	// output format - jpg/png
	'out_format' => 'jpg',
	// class name for sprites
	'css_class' => '.westeros-house',
	// relative path to atlas in css rule
	'css_path' => '../img/westeros.atlas.jpg',
	// cell size config
	'cell_padding' => 20,
	'cell_width' => 120,
	'cell_height' => 120,
	// atlas background color in [R, G, B] format
	'background' => [0xff, 0xff, 0xff]
]);

$g->generate($paths);
```


And use somewhere in html:

```html
<link rel="stylesheet" href="/assets/css/westeros.css" />

<!-- -->

<div>
	<p>Battle of the Bastards:</p>
	<span class="westeros-house westeros-house-stark"></span>
	<span class="westeros-house westeros-house-mormont"></span>
	<span class="westeros-house westeros-house-arryn"></span>
	<span class="westeros-house westeros-house-bolton"></span>
</div>
```


*P.S. I hate webpack.*

## License

MIT