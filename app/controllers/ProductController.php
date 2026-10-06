<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * ProductController - REST API for the products table.
 * Every action requires a valid Bearer (JWT) access token.
 *
 *   GET    /api/products        list products
 *   GET    /api/products/{id}   show one product
 *   POST   /api/products        add a product
 *   PUT    /api/products/{id}   update a product
 *   PATCH  /api/products/{id}   update a product (partial)
 *   DELETE /api/products/{id}   delete a product
 */
class ProductController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('Product_model');

        // Authentication gate: nobody gets past this line without a valid token.
        $this->api->rate_limit();
        $this->api->require_jwt();
    }

    private function input()
    {
        $body = $this->api->body();
        array_walk_recursive($body, function (&$v) {
            if (is_string($v)) {
                $v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
            }
        });
        return $body;
    }

    /**
     * Validate product fields.
     * $partial = true  -> only validate the keys that were sent (PATCH).
     */
    private function validate(array $in, $partial = false)
    {
        $errors = [];
        $clean  = [];

        if (!$partial || array_key_exists('product_name', $in)) {
            $name = trim((string) ($in['product_name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                $errors['product_name'] = 'Product name is required (max 100 characters).';
            }
            $clean['product_name'] = $name;
        }

        if (!$partial || array_key_exists('description', $in)) {
            $clean['description'] = trim((string) ($in['description'] ?? ''));
        }

        if (!$partial || array_key_exists('price', $in)) {
            $price = $in['price'] ?? null;
            if (!is_numeric($price) || $price < 0 || $price > 99999999.99) {
                $errors['price'] = 'Price must be a number between 0 and 99,999,999.99.';
            }
            $clean['price'] = is_numeric($price) ? round((float) $price, 2) : 0;
        }

        if (!$partial || array_key_exists('quantity', $in)) {
            $qty = $in['quantity'] ?? null;
            if (!is_numeric($qty) || (int) $qty != $qty || $qty < 0 || $qty > 2147483647) {
                $errors['quantity'] = 'Quantity must be a whole number (0 or more).';
            }
            $clean['quantity'] = is_numeric($qty) ? (int) $qty : 0;
        }

        if ($errors) {
            $this->api->respond(['error' => 'Validation failed', 'errors' => $errors, 'status' => 422], 422);
        }
        if ($partial && !$clean) {
            $this->api->respond_error('Nothing to update.', 422);
        }
        return $clean;
    }

    private function format($p)
    {
        return [
            'id'           => (int) $p['id'],
            'product_name' => $p['product_name'],
            'description'  => $p['description'],
            'price'        => (float) $p['price'],
            'quantity'     => (int) $p['quantity'],
            'created_at'   => $p['created_at'],
        ];
    }

    private function find_or_404($id)
    {
        $product = $this->Product_model->get_product($id);
        if (!$product) {
            $this->api->respond_error('Product not found.', 404);
        }
        return $product;
    }

    // GET /api/products
    public function index()
    {
        $this->api->require_method('GET');
        $rows = array_map([$this, 'format'], $this->Product_model->get_products());
        $this->api->respond(['data' => $rows, 'count' => count($rows)]);
    }

    // GET /api/products/{id}
    public function show($id)
    {
        $this->api->require_method('GET');
        $this->api->respond(['data' => $this->format($this->find_or_404($id))]);
    }

    // POST /api/products
    public function store()
    {
        $this->api->require_method('POST');
        $data = $this->validate($this->input());
        $id   = $this->Product_model->create_product($data);

        $this->api->respond([
            'message' => 'Product added successfully',
            'data'    => $this->format($this->Product_model->get_product($id)),
        ], 201);
    }

    // PUT|PATCH /api/products/{id}
    public function update($id)
    {
        $partial = ($_SERVER['REQUEST_METHOD'] === 'PATCH');
        $this->find_or_404($id);

        $data = $this->validate($this->input(), $partial);
        $this->Product_model->update_product($id, $data);

        $this->api->respond([
            'message' => 'Product updated successfully',
            'data'    => $this->format($this->Product_model->get_product($id)),
        ]);
    }

    // DELETE /api/products/{id}
    public function destroy($id)
    {
        $this->api->require_method('DELETE');
        $this->find_or_404($id);
        $this->Product_model->delete_product($id);

        $this->api->respond(['message' => 'Product deleted successfully']);
    }
}
