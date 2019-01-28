// es necesario modificar esta variable con la ubicacion de la prueba finalizando con /
var serverApi = 'https://dev.intrared.net:8083/ap/jlrodriguez/a5/';

serverApi += 'prueba_dev/app/index.php/';

$(document).ready(function () {
    obtenerClientes();
    $("#botonesIteraccion").hide();
    $("#clientes").on("change", historicoCliente);
});

// funcion general para el manejo de peticiones por ajax
async function generaAjax(ruta, datax, metodo = 'POST', tipoDeRespuesta = 'json'){
    try{
        const respuestaAjax = await $.ajax({
            type: metodo,
            url: `${serverApi}${ruta}`,
            data: datax,
            dataType: tipoDeRespuesta,
            beforeSend: ()=> lockPage2(),
            success: (response)=> response,
            complete: ()=> unlockPage2(),
            error: (xhr, ajaxOptions, thrownError) =>{
                alert(`Error en fn generaAjax, status: ${xhr.status}, error: ${thrownError}`);
            }
        });

        return respuestaAjax;
    }
    catch(e){
        alert(`Error en function generaAjax ${e.message} linea ${e.lineNumber}`);
    }
}

function lockPage2() {
    HoldOn.open({
        theme: 'sk-circle',
        message: "<h3>Cargando... Por Favor Espere.</h3>",
        backgroundColor: "#fff"
    });
}

function unlockPage2() {
    HoldOn.close();
}

async function obtenerClientes(){
    let datax = {};
    let response = await generaAjax('/clientes/all/', datax,  'GET', 'json');

    if(response.cod_status == 200){
        response = response.data;
        let optionsClientes = '';
    
        response.map(function(element, item){
            optionsClientes += `<option value="${element.customer_id}">${element.name}</option>`;
        })
    
        $("#clientes").append(optionsClientes);
    }
    else{
        alert(response.msg_status);
    }
}

async function historicoCliente(){
    let datax = {};
    let clienteSeleccionado = $("#clientes").val();

    if(clienteSeleccionado == '@'){
        return;
    }

    let fecha = new Date();
    let anio = fecha.getFullYear();
    let mes = fecha.getMonth() +1 ;

    mes = (mes < 10) ? '0'+mes : mes;   

    let fecha1 = `${anio}-${mes}-01`
    let fecha2 = `${anio}-${mes}-31`

    let response = await generaAjax(`/historicoClienteFecha/${clienteSeleccionado}/${fecha1}/${fecha2}`, datax,  'GET', 'json');

    console.log(response);
    if(response.cod_status == 200){
        response = response.data;
        let tablaHistoricoCliente = '';
        tablaHistoricoCliente += encabezadoHistoricoCliente();
        tablaHistoricoCliente += cuerpoTablaHistoricoCliente(response);
    
        $("#divHistoricoCliente").html(tablaHistoricoCliente);
        $("#ordenCompraCliente th").css("text-align", "center");
        $('#ordenCompraCliente').DataTable({
            pageLength: 50,
            dom: 'Bfrtip',
            language: {
                "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json"
            } 
        });
    
        $("#labelHistorico").show();
        tablaProductos();
    }else{
        $("#divHistoricoCliente").html("");
        $("#labelHistorico").hide();
    }

}

function encabezadoHistoricoCliente(){
    let tabla = "<table id='ordenCompraCliente' class='table table-striped table-hover' style='width:100%'>";
        tabla += "<thead>";
        tabla += "<th align='center'>Creation Date</th>";
        tabla += "<th align='center'>Order ID</th>";
        tabla += "<th align='center'>Total $</th>";
        tabla += "<th align='center'>Delivery Address</th>";
        tabla += "<th align='center'>Products</th>";
        tabla += "</thead>";

    return tabla;
}

function cuerpoTablaHistoricoCliente(data){
    let cuerpoTabla = `<tbody>`;


    data.map((element, i)=>{
        cuerpoTabla += `<tr id="fila${i}">`;
        cuerpoTabla += `<td>${element.creation_date}</td>`;
        cuerpoTabla += `<td>${element.order_id}</td>`;
        cuerpoTabla += `<td>${element.total}</td>`;
        cuerpoTabla += `<td>${element.delivery_address}</td>`;
        cuerpoTabla += `<td>${element.product}</td>`;
        cuerpoTabla += `</tr>`;
    })

    cuerpoTabla += `</tbody>`;

    return cuerpoTabla;
}

async function tablaProductos(){
    let datax = {};
    let clienteSeleccionado = $("#clientes").val();

    if(clienteSeleccionado == '@'){
        return;
    }

    let response = await generaAjax(`/productosCliente/${clienteSeleccionado}`, datax,  'GET', 'json');

    if(response.cod_status == 200){
        response = response.data;

        let tablaOrdenCompra = '';
        tablaOrdenCompra += encabezadoTablaOrdenCompra();
        tablaOrdenCompra += cuerpoTablaOrdenCompra(response);

        $("#divProductos").html(tablaOrdenCompra);
        $("#tableOrdenCompra th").css("text-align", "center");
        $('#tableOrdenCompra').DataTable({
            pageLength: 50,
            dom: 'Bfrtip',
            language: {
                "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json"
            } 
        });

        $("#labelGrilla").show();
        $("#botonesIteraccion").show();
        $(".chosen").chosen();
    }
    else{
        $("#divProductos").html(response.msg_status);
    }
}

function encabezadoTablaOrdenCompra(){
    let tabla = "<table id='tableOrdenCompra' class='table table-striped table-hover' style='width:100%'>";
        tabla += "<thead>";
        tabla += "<th align='center'>Producto</th>";
        tabla += "<th align='center'>Descripcion</th>";
        tabla += "<th align='center'>Valor</th>";
        tabla += "<th align='center'>Cantidad</th>";
        tabla += "</thead>";

    return tabla;
}

function cuerpoTablaOrdenCompra(data){
    let cuerpoTabla = `<tbody>`;

    for(let i = 0; i < 5; i++){
        cuerpoTabla += `<tr id="fila${i}">`;
            cuerpoTabla += `<td>${crearSelectorProducto(data, i)}</td>`;
            cuerpoTabla += `<td id='descripcion${i}'></td>`;
            cuerpoTabla += `<td id='valor${i}'>0</td>`;
            cuerpoTabla += `<td id='cantidad${i}'>0</td>`;
        cuerpoTabla += `</tr>`;
    }

    cuerpoTabla += `</tbody>`;

    return cuerpoTabla;
}

function crearBotonesIteraccion(){
    let html = '<div style="padding-left: 20%; padding-right: 20%;">';
    html += '<button type="button" class="btn btn-success btn-lg">Aceptar</button>';
    html += '<button type="button" class="btn btn-danger btn-lg">Cancelar</button>';
    html += '</div>';

    return html;
}

function crearSelectorProducto(data, id){
    let selectorProductor = `<select id='producto${id}' class='chosen' onchange="solicitudProducto(${id})">`;
    selectorProductor += `<option value='@'>----</option>`;

    data.map(function(element){
        selectorProductor += `<option value='${element.product_id}' data-price='${element.price}' data-descripcion='${element.product_description}'>${element.name}</option>`;
    });

    selectorProductor += '</select>';

    return selectorProductor;
}

function solicitudProducto(fila){
    let descripcion = $(`#producto${fila} option:selected`).attr('data-descripcion');
    let precio = $(`#producto${fila} option:selected`).attr('data-price');

    $(`#descripcion${fila}`).text(descripcion);
    $(`#valor${fila}`).text(precio);
    $(`#cantidad${fila}`).html(`<input type='number' name='cantidad${fila}' id='txtCantidad${fila}' min='1'>`);
}

async function ingresarOrdenCompra(){
    let datax = {};
    const regexCantida = /^\d+$/;
    let productos = [];

    for(let i=0; i<5; i++){
        let producto = $(`#producto${i} option:selected`).val();

        if(producto == '@'){
            continue;
        }

        let cantidad = $(`#txtCantidad${i}`).val();
        if(!regexCantida.test(cantidad)){
            swal("La cantidad debe ser numerica!!");
            $(`#txtCantidad${i}`).val("");
            return;
        }

        let temporal = {
            'producto': producto,
            'cantidad': cantidad
        }
        productos.push(temporal);
    }

    if(productos.length <= 0){
        return;
    }
    
    swal({
        title: "Esta seguro?",
        text: "Se creara la orden de compra!!",
        icon: "info",
        buttons: true,
      })
      .then(async (confirm) => {
        if (confirm) {
            let clienteSeleccionado = $("#clientes").val();
            
            let direccion = "direccion prueba";
            let detalles = '';
            productos.map(function(element){
                detalles += `/${element.producto}-${element.cantidad}`
            });

            let url = `crearOrden/${clienteSeleccionado}/${direccion}${detalles}`;

            let response = await generaAjax(`${url}`, datax,  'POST', 'json');
            if(response.cod_status == 200){
                swal("La orden de compra se realizo con exito!", {
                    icon: "success",
                  });
            }
            else{
                swal("No fue posible crear la orden de compra!", {
                    icon: "error",
                  });
            }
        }
      });
}

function cancelarOrdenCompra(){
    location.reload();
}