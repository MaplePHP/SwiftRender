# SwiftRender

PHP SwiftRender is a pure PHP advanced template library. It offers several advantages for developers who need to create and render templates in PHP applications. Firstly, it can improve performance, as there is no need for an additional language or engine to process the template. This can result in faster rendering times and reduced overhead. Additionally, a pure PHP template library is highly portable, as it can be used in almost any PHP application, regardless of the underlying platform or framework.

PHP SwiftRender offers greater flexibility and control over the rendering process. Developers that knows PHP can structure and design their templates in ways that better suit their specific needs, resulting in more efficient and effective templates. 

PHP is a widely-used language, most developers are already familiar with its syntax and conventions. This makes it easier to learn and use a pure PHP template library than an entirely new templating language or engine.


## Usage


### Initialisation
One time setup to use through the application.

```php

use PHPFuse\Output\SwiftRender;
use PHPFuse\DTO\Format;

$swift = new SwiftRender();

$swift->setIndexDir(dirname(__FILE__)."/resources/") // Set index directory
->setViewDir(dirname(__FILE__)."/resources/views/")  // Set view directory
->setPartialDir(dirname(__FILE__)."/resources/partials/"); // Set partials directory

// Prepare/bind "/resources/index.php"
$swift->setIndex("index"); 

// Prepare/bind "/resources/views/main.php"
$swift->setView("main");

// Prepare/bind "/resources/partials/article.php"
$swift->setPartial("article", [
	"date" => "2023-02-30 15:33:22",
    "name" => "This is an article",
    "content" => "Lorem ipsum dolor sit amet, consectetur adipisicing elit.",
    "feed" => [
        [
            "headline" => "test 1", 
            "description" => "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sunt, architecto."
        ],
        [
            "headline" => "test 2", 
            "description" => "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sunt, architecto."
        ]
    ]
]);
// Keep in mind that the data usally comes from the database and that it should/might be called from you controller.
// E.g. $swift->setPartial("article", $mysqli->fetch_objects());

```

### Templating

#### Index

The file **/resources/index.php** has already been binded under the initialisation section above **$swift->setIndex("index")**. The file looks like this:

```html
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Used to create dynamic HTML tags, explained bellow under the section "Easy DOM manipulation" -->
	<?php echo \PHPFuse\Output\Dom\Document::dom("head")->execute(); ?>
</head>
<body>
	<?php echo $this->partial("navigation")->get(); ?>
	<main>
		<?php echo $this->view()->get(); ?>
	</main>
</body>
</html>
```

#### View
The file **/resources/views/main.php** has already been binded under the initialisation section above **$swift->setView("main")**. The file looks like this:

```html
<div id="wrapper">
	<?php echo $this->partial("article")->get(); ?>
</div>
```

#### Partial
The file **/resources/partials/article.php** has already been binded under the initialisation section above **$swift->setPartial("article", ...)**. The file looks like this:

```html
<article>
	<header>
		<h2><?php echo $obj->name; ?></h2>
		<h6><?php echo $obj->date("DateTime")->format("Y/m/d"); ?></h6>
		<p><?php echo $obj->content("Str")->excerpt(20)->get(); ?></p>
	</header>
	<?php if($obj->feed()->count() > 0): ?>
	<ul>
		<?php foreach($obj->feed()->fetch()->get() as $row): ?>
		<li>
			<strong><?php echo $row->headline("Str")->ucfirst()->get(); ?></strong><br>
			<?php echo $row->description; ?>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</article>
```

#### Partial functionality
The partials all arguments will automatically be converted to a object with alot of extedned functionallity. Here is some:

```php
echo $obj->date; // 2023-02-30 15:33:22
echo $obj->date("DateTime")->format("Y/m/d"); // 2023/02/30
// Will strip all html tags, replace regular line breaks with "<br>" and uppercase the first letter
echo $obj->content("Str")->stripTags()->nl2br()->ucfirst()->get();

// Loop through an array
if($obj->feed()->count() > 0) foreach($obj->feed()->fetch()->get() as $row) {
	echo $row->headline("Str")->ucfirst()->get()."<br>";
}
```

#### Run the template engine
You can run the template engine later in a empty file, emitter or router dipatcher. It all depends on your setup.
```php
echo $swift->index()->get();

```

#### Dynamic views
You can also create a dynamic view that will over write the current view if called. This is great for e.g. showing a 404 page. 

In this example the current view which is **/resources/views/main** will be replaced with the view **/resources/views/httpStatus.php** when response status code is (403, 404 e.g.).

```php
// PHP Fuse framework (PSR response). Just using this in this example to handle status response codes
use PHPFuse\Http\Response;

$swift->bindToBody(
    "httpStatus",
    Format\Arr::value(Response::PHRASE)->unset(200, 201, 202)->arrayKeys()->get()
    // This method will load all HTTP Request status codes (like 403, 404 e.g.) except for (200, 201, 202)
);

$swift->findBind($response->getStatusCode());
```

#### Easy DOM manipulation
Advance DOM creation and works great with stuff like the Meta data becouse you can later in change values and attributes in the controller. 

```php
// Advance DOM creation and works great with stuff like the Meta data 
$dom = PHPFuse\Output\Dom\Document::dom("head");
$dom->bindTag("title", "title")->setValue("Meta title");
$dom->bindTag("meta", "description")->attr("name", "Description")->attr("content", "Lorem ipsum dolor sit amet.");

// Then later in controller you can change the meta title and description
$head = PHPFuse\Output\Dom\Document::dom("head");
$head->getElement("title")->setValue("New meta title");
$head->getElement("description")->attr("content", "New meta description...");

```
