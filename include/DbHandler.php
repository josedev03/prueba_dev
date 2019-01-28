<?php

class DbHandler {
 
    public $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';

        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    
    // retorna database connection
    public function getConnection(){
        return $this-> conn;
    }  
      
    // cierra la conexion con la base de datos
    public function closeConnection(){
        return $this-> conn = NULL;
    }

    // retorna el listado de clientes
    public function obtenerListaClientes( $mData = array() ){
        try 
        {
            $query = "
                SELECT * 
                  FROM ".DB_NAME.".customer";
            $statement = $this -> conn -> prepare( $query );
            $statement -> execute();
            $mResult = $statement -> fetchAll(PDO::FETCH_ASSOC);  
            $statement -> closeCursor();          
            return sizeof($mResult) > 0 ? $mResult : false;
        } 
        catch (Exception $e) 
        {
            echo "<pre>Excep: "; print_r($e); echo "</pre>";
        }
    }

    // retorna los productos asociados a un cliente
    public function obtenerProductosCliente( $mData = array() ){
        try 
        {
            $query = "
                SELECT c.*
                  FROM ".DB_NAME.".customer a
            INNER JOIN ".DB_NAME.".customer_product b ON a.customer_id = b.customer_id
            INNER JOIN ".DB_NAME.".product c ON b.product_id = c.product_id
                 WHERE a.customer_id = :customer_id ";
            $statement = $this -> conn -> prepare( $query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $statement -> execute( $mData );
            $mResult = $statement -> fetchAll(PDO::FETCH_ASSOC);  
            $statement -> closeCursor();       
            return sizeof($mResult) > 0 ? $mResult : false;
        } 
        catch (Exception $e) 
        {
            echo "<pre>Excep: "; print_r($e); echo "</pre>";
        }
    }

    // retorna todas las ordenes creadas por un cliente en un rango de fechas
    public function obtenerOrdenesClientePorFecha( $mData = array() ){

        $validacionFecha1 = preg_match('/^(\d{4})(-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/', $mData[':fecha1']) ? true : false;
        $validacionFecha2 = preg_match('/^(\d{4})(-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/', $mData[':fecha2']) ? true : false;

        if($validacionFecha1 && $validacionFecha2){
            $query = "
                SELECT b.creation_date, b.order_id, b.total, b.delivery_address, concat( sum(c.quantity), ' X ', c.product_description ) as product 
                  FROM ".DB_NAME.".customer as a
            INNER JOIN ".DB_NAME.".order as b ON a.customer_id = b.customer_id
            INNER JOIN ".DB_NAME.".order_detail as c ON b.order_id = c.order_id
                 WHERE a.customer_id = :customer_id
                   AND b.creation_date BETWEEN :fecha1 AND :fecha2 
              GROUP BY c.product_id";
            $statement = $this -> conn -> prepare( $query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $statement -> execute( array(':customer_id' => $mData[':customer_id'], ':fecha1' => $mData[':fecha1'], ':fecha2' => $mData[':fecha2'] ) );
            $mResult = $statement -> fetchAll(PDO::FETCH_ASSOC);  
            $statement -> closeCursor();
            return sizeof($mResult) > 0 ? $mResult : false;
        }
    }

    // retorna la validacion si un cliente especifico existe
    public function validaCliente($mData = array() ){
        try 
        {
            $query = "
                SELECT name
                  FROM ".DB_NAME.".customer
                 WHERE customer_id = :customer_id";
            $statement = $this -> conn -> prepare( $query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $statement -> execute( $mData );
            $mResult = $statement -> fetchAll(PDO::FETCH_ASSOC);  
            $statement -> closeCursor();
            return sizeof($mResult) > 0 ? $mResult : false;
        } 
        catch (Exception $e) 
        {
            echo "<pre>Excep: "; print_r($e); echo "</pre>";
        }
    }

    // retorna la informacion asociada a un producto especifico
    public function obtenerProducto( $mData = array() ){
        try{
            $query = "
                    SELECT *
                      FROM ".DB_NAME.".product
                     WHERE product_id = :product_id";
            $statement = $this -> conn -> prepare( $query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $statement -> execute( $mData );
            $mResult = $statement -> fetchAll(PDO::FETCH_ASSOC);  
            $statement -> closeCursor();
            return sizeof($mResult) > 0 ? $mResult : false;
        }
        catch (Exception $e) 
        {
            echo "<pre>Excep: "; print_r($e); echo "</pre>";
        }
    }

    // retorna el siguiente consecutivo para crear una orden
    public function obtenerSiguienteConsecutivoOrden(){
        try{
            $query = "
                    SELECT IF( MAX(order_id) <= 0, 1, MAX(order_id)+1 ) as consecutivo
                      FROM ".DB_NAME.".order";
            $statement = $this -> conn -> prepare( $query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $statement -> execute( $mData );
            $mResult = $statement -> fetchAll(PDO::FETCH_ASSOC);  
            $statement -> closeCursor();
            return sizeof($mResult) > 0 ? $mResult[0]['consecutivo'] : false;
        }
        catch (Exception $e) 
        {
            echo "<pre>Excep: "; print_r($e); echo "</pre>";
        }
    }

    // crea una orden (tabla: order)
    public function crearOrden($mData = array())
    {
        try{
            $query = "INSERT INTO ".DB_NAME.".order (customer_id, creation_date, delivery_address, total) VALUES (?, NOW(), ?, ?)";
            $statement = $this -> conn -> prepare( $query );
            $estado = $statement -> execute( [ $mData[':customer_id'], $mData[':delivery_address'], $mData[':total'] ] );

            return ($estado) ? true : false;   
        }
        catch (Exception $e) 
        {
            echo "<pre>Excep: "; print_r($e); echo "</pre>";
        }
    }

    // crea los detalles asociados a una orden (tabla: order_detail)
    public function crearOrdenDetalle( $mData = array() ){
        try{
            $query = "INSERT INTO ".DB_NAME.".order_detail (product_description, price, order_id, quantity, product_id) VALUES ";
            $query .= $mData[':query'];
            $statement = $this -> conn -> prepare( $query );
            $estado = $statement -> execute();

            return ($estado) ? true : false;   
        }
        catch (Exception $e) 
        {
            echo "<pre>Excep: "; print_r($e); echo "</pre>";
        }
    }
}
 
?>