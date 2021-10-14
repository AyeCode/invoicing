<?php
/**
 * Template that prints additional code in the footer when viewing a blog on the frontend..
 *
 * This template can be overridden by copying it to yourtheme/invoicing/frontend-head.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<style>
	.getpaid-price-buttons label{
		transition: all .3s ease-out;
		text-align: center;
		padding: 10px 20px;
		background-color: #eeeeee;
		border: 1px solid #e0e0e0;
	}

	.getpaid-price-circles label {
		padding: 0 4px;
		-moz-border-radius:50%;
		-webkit-border-radius: 50%;
		border-radius: 50%;
	}

	.getpaid-price-circles label span{
		display: block;
		padding: 50%;
		margin: -3em -50% 0;
		position: relative;
		top: 1.5em;
		border: 1em solid transparent;
		white-space: nowrap;
	}

	.getpaid-price-buttons input[type="radio"]{
		visibility: hidden;
		height: 0;
		width: 0 !important;
	}

	.getpaid-price-buttons input[type="radio"]:checked + label,
	.getpaid-price-buttons label:hover {
		color: #fff;
		background-color: #1e73be;
		border-color: #1e73be;
	}

	.getpaid-public-items-archive-single-item .inner {
		box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
	}

	.getpaid-public-items-archive-single-item:hover .inner{
		box-shadow: 0 1px 4px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.30);
	}

	.wp-block-getpaid-public-items-getpaid-public-items-loop .item-name {
		font-size: 1.3rem;
	}

	.getpaid-subscription-item-actions {
		color: #ddd;
		font-size: 13px;
		padding: 2px 0 0;
		position: relative;
		left: -9999em;
	}

	.getpaid-subscriptions-table-row:hover .getpaid-subscription-item-actions {
		position: static;
	}

	.getpaid-subscriptions table {
		font-size: 0.9em;
		table-layout: fixed;
	}

	.getpaid-subscriptions-table-column-subscription {
		font-weight: 500;
	}

	.getpaid-subscriptions-table-row span.label {
		font-weight: 500;
	}

	.getpaid-subscriptions.bsui .table-bordered thead th {
		border-bottom-width: 1px;
	}

	.getpaid-subscriptions.bsui .table-striped tbody tr:nth-of-type(odd) {
		background-color: rgb(0 0 0 / 0.01);
	}

	.wpinv-page .bsui a.btn {
		text-decoration: none;
	}

	.getpaid-cc-card-inner {
		max-width: 460px;
	}

	.getpaid-payment-modal-close {
		position: absolute;
		top: 0;
		right: 0;
		z-index: 200;
	}

	.getpaid-form-cart-item-price {
		min-width: 120px !important;
	}

	/* Fabulous Fluid theme fix */
	#primary .getpaid-payment-form p {
		float: none !important;
	}

	.bsui .is-invalid ~ .invalid-feedback, .bsui .is-invalid ~ .invalid-tooltip {
		display: block
	}

	.bsui .is-invalid {
		border-color: #dc3545 !important;
	}

	.getpaid-file-upload-element{
		height: 200px;
		border: 3px dashed #dee2e6;
		cursor: pointer;
	}

	.getpaid-file-upload-element:hover{
		border: 3px dashed #424242;
	}

	.getpaid-file-upload-element.getpaid-trying-to-drop {
	    border: 3px dashed #8bc34a;
		background: #f1f8e9;
	}
</style>
