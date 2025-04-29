/**
 * Internal dependencies
 */
import Inspector from './inspector';

/**
 * WordPress dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Fragment, Component } from '@wordpress/element';
const { serverSideRender: ServerSideRender } = wp;

class edit extends Component {

	componentDidMount() {
		this.initializeFormComponents();
	}

	componentDidUpdate() {
		this.initializeFormComponents();
	}

	initializeFormComponents() {
		// load recaptcha js here
		const recaptchaField = document.querySelector('.ht-form-elem-recaptcha-field');
		if (recaptchaField) {
			
			// Check if we need v2 or v3
			const isV2 = recaptchaField.classList.contains('ht-form-elem-recaptcha-field-v2');
			const isV3 = recaptchaField.classList.contains('ht-form-elem-recaptcha-field-v3');

			// Load reCAPTCHA v2 if needed
			if (isV2) {
				const script = document.createElement('script');
				script.src = 'https://www.google.com/recaptcha/api.js';
				script.async = true;
				script.defer = true;
				document.head.appendChild(script);
			}
			
			// Load reCAPTCHA v3 if needed
			if (isV3) {
				const siteKey = document.querySelector('.g-recaptcha').getAttribute('data-sitekey');
				if (siteKey) {
					const script = document.createElement('script');
					script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
					script.async = true;
					script.defer = true;
					document.head.appendChild(script);
				}
			}
		}
	    // Handle Range Slider Value Update
		if(document.querySelectorAll('.ht-form-elem-range')) {
			document.querySelectorAll('.ht-form-elem-range').forEach((range) => {
				range.addEventListener('input', () => {
					range.nextElementSibling.querySelector('.ht-form-elem-range-amount').textContent = range.value;
				});
			});
		}
	
		// Input Mask
		if(document.querySelectorAll('[data-mask]')) {
			document.querySelectorAll('[data-mask]').forEach((input) => {
				const maskFormat = input.getAttribute('data-mask');
				
				if (maskFormat) {
					let maskOptions = {};
					
					// Configure specific formats
					if (maskFormat === 'MM/DD/YYYY') {
						// Date mask with M/D/Y format
						maskOptions = {
							alias: 'datetime',
							inputFormat: 'MM/DD/YYYY',
						};
					} else if (maskFormat === 'HH:MM') {
						// Time mask
						maskOptions = {
							alias: 'datetime',
							inputFormat: 'HH:mm',
							placeholder: 'HH:MM'
						};
					} else if (maskFormat === '9999 9999 9999 9999') {
						// Credit card mask
						maskOptions = {
							mask: '9999 9999 9999 9999'
						};
					} else if (maskFormat === '$999.99') {
						// Currency mask
						maskOptions = {
							alias: 'numeric',
							groupSeparator: '',
							digits: 2,
							digitsOptional: false,
							prefix: '$',
							rightAlign: false,
							allowMinus: false,
						};
					} else if (maskFormat === '(999) 999-9999') {
						// Phone mask
						maskOptions = {
							mask: '(999) 999-9999'
						};
					} else if (maskFormat === '999-99-9999') {
						// SSN mask
						maskOptions = {
							mask: '999-99-9999'
						};
					} else if (maskFormat === '99999-9999') {
						// Zip code mask
						maskOptions = {
							mask: '99999-9999'
						};
					} else {
						// Default - use the format as is
						maskOptions = {
							mask: maskFormat
						};
					}
					
					// Apply the mask and store reference for validation
					const im = new Inputmask(maskOptions);
					im.mask(input);
				}
			});
		}
		
		// Custom Select using Choices JS
		if(document.querySelectorAll('[data-ht-select]')) {
			document.querySelectorAll('[data-ht-select]').forEach((select) => {
				const searchable = select.getAttribute('data-searchable') === '1';
				const maxselect = select.getAttribute('data-maxselect') ? parseInt(select.getAttribute('data-maxselect')) : -1;
				new Choices(select, {
					searchEnabled: searchable,
					itemSelectText: '',
					maxItemCount: maxselect,
					removeItemButton: true,
					placeholder: true,
					placeholderValue: '',
					shouldSort: false,
				});
			});
		}
	}

	render (){
		const{
			clientId,
			attributes,
			className,
			setAttributes,
		} = this.props;

		const { formId, blockUniqId } = attributes;

		{ ( blockUniqId == '' ) && setAttributes( { blockUniqId: clientId } ) }

		const areaClasses = classnames( 
			className,
			{ [ `ht-contactform-area-${ attributes.align }` ] : attributes.align }
		);

		let htBlockUniqId = `ht-editor-bock-${blockUniqId}`;

		return (
			<Fragment>
				<div id={ htBlockUniqId } className={ areaClasses }>
					<ServerSideRender
						block="block/ht-form"
						attributes = {{formId:formId}}
					/>
				</div>
	     		<Inspector { ...this.props } />
		    </Fragment>
		);
	}
}

export default edit;
