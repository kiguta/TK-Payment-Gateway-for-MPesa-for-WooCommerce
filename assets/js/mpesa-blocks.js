( function( wc, wp ) {
	var registerPaymentMethod = wc.wcBlocksRegistry.registerPaymentMethod;
	var getSetting            = wc.wcSettings.getSetting;
	var createElement         = wp.element.createElement;
	var useState              = wp.element.useState;
	var useEffect             = wp.element.useEffect;
	var __                    = wp.i18n.__;

	var settings = getSetting( 'tk_mpesa_data', {} );

	var Label = function() {
		return createElement( 'span', null, settings.title || __( 'MPesa Payments', 'tk-mpesa-payments-for-woocommerce' ) );
	};

	var Content = function( props ) {
		var eventRegistration = props.eventRegistration;
		var emitResponse      = props.emitResponse;

		var phoneState = useState( '' );
		var phone      = phoneState[0];
		var setPhone   = phoneState[1];

		useEffect( function() {
			var unsubscribe = eventRegistration.onPaymentSetup( function() {
				if ( ! phone ) {
					return {
						type:    emitResponse.responseTypes.ERROR,
						message: __( 'MPesa Phone Number is required!', 'tk-mpesa-payments-for-woocommerce' ),
					};
				}
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: { phonenumber: phone },
					},
				};
			} );
			return unsubscribe;
		}, [ phone, eventRegistration.onPaymentSetup, emitResponse.responseTypes ] );

		return createElement(
			'div',
			null,
			settings.description
				? createElement( 'p', null, settings.description )
				: null,
			createElement(
				'div',
				{ className: 'form-row form-row-wide' },
				createElement(
					'label',
					{ htmlFor: 'tk-mpesa-phone' },
					__( 'MPesa Phone Number', 'tk-mpesa-payments-for-woocommerce' ),
					' ',
					createElement( 'span', { className: 'required', 'aria-hidden': 'true' }, '*' )
				),
				createElement( 'input', {
					id:          'tk-mpesa-phone',
					name:        'phonenumber',
					type:        'tel',
					value:       phone,
					onChange:    function( e ) { setPhone( e.target.value ); },
					placeholder: '0XXX XXX XXX',
					autoComplete: 'off',
					required:    true,
					style:       { width: '100%', height: '30px' },
				} )
			)
		);
	};

	registerPaymentMethod( {
		name:           'tk_mpesa',
		label:          createElement( Label, null ),
		content:        createElement( Content, null ),
		edit:           createElement( Content, null ),
		canMakePayment: function() { return true; },
		ariaLabel:      settings.title || __( 'MPesa Payments', 'tk-mpesa-payments-for-woocommerce' ),
		supports:       { features: settings.supports || [ 'products' ] },
	} );

} )( window.wc, window.wp );
