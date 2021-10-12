<?php
/**
 * Displays a price select preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/price_select.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<legend class='col-form-label' v-if='form_element.label' v-html="form_element.label"></legend>

<!-- Buttons -->
<div v-if='form_element.select_type=="buttons"' class="getpaid-price-buttons">
    <span v-for="(option, index) in form_element.options.split(',')" :key="index"  class='d-inline-block'>
        <input type="radio" :id="form_element.id + index" :checked="index==0" />
        <label :for="form_element.id + index" class="rounded">{{option | optionize}}</label>
    </span>
</div>

<!-- Circles -->
<div v-if='form_element.select_type=="circles"' class="getpaid-price-buttons getpaid-price-circles">
    <span v-for="(option, index) in form_element.options.split(',')" :key="index" class='d-inline-block'>
        <input type="radio" :id="form_element.id + index" :checked="index==0" />
        <label :for="form_element.id + index"><span>{{option | optionize}}</span></label>
    </span>
</div>

<!-- Radios -->
<div v-if='form_element.select_type=="radios"'>
    <div v-for="(option, index) in form_element.options.split(',')" :key="index">
        <label>
            <input type="radio" :checked="index==0" />
            <span>{{option | optionize}}</span>
        </label>
    </div>
</div>

<!-- Checkboxes -->
<div v-if='form_element.select_type=="checkboxes"'>
    <div v-for="(option, index) in form_element.options.split(',')" :key="index">
        <label>
            <input type="checkbox" :checked="index==0" />
            <span>{{option | optionize}}</span>
        </label>
    </div>
</div>

<!-- Select -->
<select v-if='form_element.select_type=="select"' class='form-control custom-select'>
    <option v-if="form_element.placeholder" selected="selected">
        {{form_element.placeholder}}
    </option>
</select>

<small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
