<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Product_model
 *
 * All database access for the `products` table lives here.
 * Columns: id, product_name, description, price, quantity, created_at
 */
class Product_model extends Model
{
    protected $table       = 'products';
    protected $primary_key = 'id';
    protected $fillable    = ['product_name', 'description', 'price', 'quantity'];

    /** Return every product, newest first. */
    public function get_products()
    {
        return $this->db->table($this->table)
                        ->order_by('id', 'DESC')
                        ->get_all();
    }

    /** Return one product (array) or false when it does not exist. */
    public function get_product($id)
    {
        return $this->db->table($this->table)
                        ->where('id', (int) $id)
                        ->get();
    }

    /** Insert a product and return the new id. */
    public function create_product(array $data)
    {
        $this->db->table($this->table)->insert([
            'product_name' => $data['product_name'],
            'description'  => $data['description'],
            'price'        => $data['price'],
            'quantity'     => $data['quantity'],
        ]);
        return (int) $this->db->last_id();
    }

    /** Update a product (only the keys supplied in $data). */
    public function update_product($id, array $data)
    {
        $allowed = array_intersect_key($data, array_flip($this->fillable));
        if (empty($allowed)) {
            return 0;
        }
        return $this->db->table($this->table)
                        ->where('id', (int) $id)
                        ->update($allowed);
    }

    /** Delete a product. */
    public function delete_product($id)
    {
        return $this->db->table($this->table)
                        ->where('id', (int) $id)
                        ->delete();
    }
}
