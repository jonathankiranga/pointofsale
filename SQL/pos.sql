/*
LOAD DATA INFILE '/path/to/file.csv'
INTO TABLE products
FIELDS TERMINATED BY ',' 
LINES TERMINATED BY '\n'
(barcode, product_name, category_id, price);


-- Table to store the receipt details
CREATE TABLE sales_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total DECIMAL(10, 2) NOT NULL,
    cash DECIMAL(10, 2) NOT NULL,
    change_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to store the sales items for each receipt
CREATE TABLE sales_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    barcode VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (receipt_id) REFERENCES sales_receipts(id)
);
*/
   
alter table `SalesHeader` add column `total` float,add column  `cash` float, add column `change_amount` float;
