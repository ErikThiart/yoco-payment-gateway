# yoco-payment-gateway
Their documentation is a bit lacking, so decided to create a class to make use of their API for my own documentation in future projects.

# Yoco Class

The `Yoco` class provides a flexible and convenient way to integrate with the Yoco payment gateway in your PHP applications. It allows you to create checkouts, manage line items, and perform API requests to handle payment transactions.

## Features

- Create and manage Yoco checkouts.
- Add detailed line items to the checkout.
- Configure URLs for success, cancel, and failure outcomes.
- Perform API requests to initiate transactions.

## Requirements

- PHP 7.0 or higher
- cURL extension

## Installation

1. Clone or download this repository to your project.
2. Include the `Yoco.php` file in your project's codebase.

## Usage

Here's a basic example of how to use the `Yoco` class:

```php
use App\Classes\Yoco;

// Create an instance of the Yoco class
$yoco = new Yoco();

// Set URLs for success, cancel, and failure
$yoco->setUrls('https://your_app.com/success', 'https://your_app.com/cancel', 'https://your_app.com/failure');

// Add items to the checkout
$item = [
    "displayName" => "Product Name",
    "description" => "Product Description",
    "quantity" => 1,
    "pricingDetails" => [
        "price" => 1000, // Price in cents
    ],
];
$yoco->addItem($item);

// Perform checkout
$response = $yoco->checkout(1000, 'Order Note');

if ($response) {
    echo "Checkout initiated successfully!";
    //Grab the redirect URL and send the customer there using a new browser TAB
    // ...
} else {
    echo "Checkout failed to initiate.";
}
```

## Author
Erik Thiart
