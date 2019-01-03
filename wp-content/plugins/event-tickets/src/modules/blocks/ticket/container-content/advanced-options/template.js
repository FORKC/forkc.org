/**
 * External dependencies
 */
import React, { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import uniqid from 'uniqid';

/**
 * WordPress dependencies
 */
import { Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Accordion } from '@moderntribe/common/elements';
import './style.pcss';
import Duration from './duration/container';
import SKU from './sku/container';
import EcommerceOptions from './ecommerce-options/container';
import MoveDelete from './move-delete/container';

class AdvancedOptions extends Component {
	static propTypes = {
		blockId: PropTypes.string.isRequired,
		isDisabled: PropTypes.bool,
		hasBeenCreated: PropTypes.bool,
	};

	constructor( props ) {
		super( props );
		this.accordionId = uniqid();
	}

	getHeader = () => (
		<Fragment>
			<Dashicon
				className="tribe-editor__ticket__advanced-options-header-icon"
				icon="arrow-down"
			/>
			<span className="tribe-editor__ticket__advanced-options-header-text">
				{ __( 'Advanced Options', 'event-tickets' ) }
			</span>
		</Fragment>
	);

	getContent = () => (
		<Fragment>
			<Duration blockId={ this.props.blockId } />
			<SKU blockId={ this.props.blockId } />
			<EcommerceOptions blockId={ this.props.blockId } />
			{ this.props.hasBeenCreated && (
				<MoveDelete blockId={ this.props.blockId } />
			) }
		</Fragment>
	);

	getRows = () => ( [
		{
			accordionId: this.accordionId,
			content: this.getContent(),
			contentClassName: 'tribe-editor__ticket__advanced-options-content',
			header: this.getHeader(),
			headerAttrs: { disabled: this.props.isDisabled },
			headerClassName: 'tribe-editor__ticket__advanced-options-header',
		},
	] );

	render() {
		return (
			<Accordion
				className="tribe-editor__ticket__advanced-options"
				rows={ this.getRows() }
			/>
		);
	}
}

export default AdvancedOptions;
