/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Configuración inicial requerida por FOSJsRoutingBundle
const routes = require('./js_routes.json');
import Routing from  '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';

Routing.setRoutingData(routes);

const $ = require('jquery');

// create global $ and jQuery variables
global.$ = global.jQuery = $;

// this "modifies" the jquery module: adding behavior to it
// the bootstrap module doesn't export/return anything
require('bootstrap');
require('bootstrap-datepicker');

require( 'jszip');
require( 'pdfmake');
require( 'datatables.net-bs4');
require( 'datatables.net-buttons-bs4');
require( 'datatables.net-buttons/js/buttons.colVis.js');
require( 'datatables.net-buttons/js/buttons.flash.js');
require( 'datatables.net-buttons/js/buttons.html5.js');
require( 'datatables.net-buttons/js/buttons.print.js');
require( 'datatables.net-fixedcolumns-bs4');
require( 'datatables.net-fixedheader-bs4');
require( 'datatables.net-keytable-bs4');
require( 'datatables.net-responsive-bs4');
require( 'datatables.net-rowgroup-bs4');
require( 'datatables.net-scroller-bs4');
require( 'datatables.net-select-bs4');

var dt = require( 'datatables.net' );
