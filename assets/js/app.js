/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

const $ = require('jquery');

// create global $ and jQuery variables
global.$ = global.jQuery = $;

// this "modifies" the jquery module: adding behavior to it
// the bootstrap module doesn't export/return anything
require('bootstrap');

// Incluye librería para ingreso de fecha y hora
//  [No se pudieron incluir. Se pone en la plantilla de base y se descargan los archivos js en public/js]
//  [Los Archivos son: ]
//  [https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js]
//  [https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/js/tempusdominus-bootstrap-4.min.js]
// 
//require('popper');
//require('tempusdominus-bootstrap-4');

require('bootstrap-datepicker');
//require('~bootstrap-datepicker/dist/locales(bootstrap-datepicker.es.min.js')

