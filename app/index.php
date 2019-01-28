<?php

ini_set('display_errors', true);
error_reporting(E_ALL & ~E_NOTICE);

//INCLUDES
include_once '../include/config.php';
require_once '../include/DbHandler.php'; 

require '../libs/Slim/Slim.php'; 
\Slim\Slim::registerAutoloader(); 
$app = new \Slim\Slim();

$app->get('/clientes/all/', function() use ($app){
    $db = new DbHandler();
    $listaClientes = $db->obtenerListaClientes();
    $db->closeConnection();
    unset($db);

    if($listaClientes){
        $mensaje = array('cod_status' => 200, 'data' => $listaClientes);
        print_r( json_encode($mensaje) );
    }
    else{
        $mensaje = array('cod_status' => 400, 'msg_status' => 'No se encontraron resultados');
        print_r( json_encode($mensaje));
    }
});

$app->get('/productosCliente/:customer_id', function($customer_id) use ($app){
    $db = new DbHandler();
    $productosCliente = $db->obtenerProductosCliente( array( ':customer_id' => $customer_id) );
    $db->closeConnection();
    unset($db);

    if($productosCliente){
        $mensaje = array('cod_status' => 200, 'data' => $productosCliente);
        print_r( json_encode($mensaje) );
    }
    else{
        $mensaje = array('cod_status' => 400, 'msg_status' => 'No se encontraron resultados');
        print_r( json_encode($mensaje));
    }
});

$app->get('/historicoClienteFecha/:customer_id/:fecha1/:fecha2', function($customer_id, $fecha1, $fecha2) use ($app){
    $db = new DbHandler();
    $productosCliente = $db->obtenerOrdenesClientePorFecha( array( ':customer_id' => $customer_id, ':fecha1' => $fecha1, ':fecha2' => $fecha2) );
    $db->closeConnection();
    unset($db);

    if($productosCliente ){
        $mensaje = array('cod_status' => 200, 'data' => $productosCliente);
        print_r( json_encode($mensaje) );
    }
    else{
        $mensaje = array('cod_status' => 400, 'msg_status' => 'No se encontraron resultados');
        print_r( json_encode($mensaje));
    }
});

// product_id: se debe recibir el id del cliente
// delivery_address: direccion de entrega de la orden, no puede estar vacia
// product_quantity: se debe recibir el id del producto seguido un guion(-) seguido la cantidad del producto
$app->post('/crearOrden/:customer_id/:delivery_address/:product_quantity+', function($customer_id, $address, $product_quantity){
    $db = new DbHandler();

    $address = trim($address);

    if( empty($address) || $address == ''){
        $mensaje = array('cod_status' => 400, 'msg_status' => 'Direccion no valida');
        print_r( json_encode($mensaje));
        exit;
    }

    if( sizeof($product_quantity) > 5){
        $mensaje = array('cod_status' => 400, 'msg_status' => 'El maximo de productos por cliente son 5');
        print_r( json_encode($mensaje));
        exit;
    }

    $cliente = $db -> validaCliente( array( ':customer_id' => $customer_id) );
    if($cliente){
        $consecutivoOrden = $db -> obtenerSiguienteConsecutivoOrden();
        $productosCliente = $db -> obtenerProductosCliente( array( ':customer_id' => $customer_id) );

        $productosValidos = array();
        foreach ($productosCliente as $key => $value) {
            $productosValidos[] = $value['product_id'];
        }

        $queryDetail = '';
        $totalOrden = 0;
        foreach ($product_quantity as $key => $value) {
            $item = explode("-", $value);
            $produc_id = $item[0];
            $cantidad = $item[1];

            if( !in_array($produc_id, $productosValidos) ){
                $mensaje = array('cod_status' => 400, 'msg_status' => 'Producto no valido');
                print_r( json_encode($mensaje));
                exit;
            }

            $producto = $db -> obtenerProducto( array(':product_id' => $produc_id ) );
            $totalOrden += ( $producto[0]['price'] * 1 * $cantidad);
            $queryDetail .= "('{$producto[0]['product_description']}', {$producto[0]['price']}, {$consecutivoOrden}, {$cantidad}, {$produc_id}),";
        }

        $queryDetail = substr($queryDetail, 0, -1);

        $orden = $db->crearOrden( array( ':customer_id' => $customer_id, ':delivery_address' => $address, ':total' => $totalOrden) );

        if(orden){
            $ordenDetalle = $db->crearOrdenDetalle( array( ':query' => $queryDetail) );

            if($ordenDetalle){
                $mensaje = array('cod_status' => 200, 'msg_status' => 'La orden se creo exitosamente');
                print_r( json_encode($mensaje));
            }
            else{
                $mensaje = array('cod_status' => 400, 'msg_status' => 'los detalles de la orden no pudieron ser creados');
                print_r( json_encode($mensaje));
                exit;
            }
        }
        else{
            $mensaje = array('cod_status' => 400, 'msg_status' => 'la orden no pudo ser creada');
            print_r( json_encode($mensaje));
            exit;
        }

        $db->closeConnection();
        unset($db);
    }
    else{
        $mensaje = array('cod_status' => 400, 'msg_status' => 'El cliente no existe');
        print_r( json_encode($mensaje));
        exit;
    }
});

$app->run();
?> 