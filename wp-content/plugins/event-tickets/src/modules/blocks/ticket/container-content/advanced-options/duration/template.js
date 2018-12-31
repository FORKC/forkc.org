/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DateTimeRangePicker, LabelWithTooltip } from '@moderntribe/tickets/elements';
import './style.pcss';

const TicketDuration = ( props ) => (
	<div className={ classNames(
		'tribe-editor__ticket__duration',
		'tribe-editor__ticket__content-row',
		'tribe-editor__ticket__content-row--duration',
	) }>
		<LabelWithTooltip
			className="tribe-editor__ticket__duration-label-with-tooltip"
			label={ __( 'Sale Duration', 'event-tickets' ) }
			tooltipText={ __(
				'If you do not set a start sale date, tickets will be available immediately.',
				'event-tickets',
			) }
			tooltipLabel={ <Dashicon className="tribe-editor__ticket__tooltip-label" icon="info-outline" /> }
		/>
		<DateTimeRangePicker
			className="tribe-editor__ticket__duration-picker"
			{ ...props }
		/>
	</div>
);

TicketDuration.propTypes = {
	fromDate: PropTypes.string,
	fromDateDisabled: PropTypes.bool,
	fromTime: PropTypes.string,
	fromTimeDisabled: PropTypes.bool,
	onFromDateChange: PropTypes.func,
	onFromTimePickerBlur: PropTypes.func,
	onFromTimePickerChange: PropTypes.func,
	onFromTimePickerClick: PropTypes.func,
	onToDateChange: PropTypes.func,
	onToTimePickerBlur: PropTypes.func,
	onToTimePickerChange: PropTypes.func,
	onToTimePickerClick: PropTypes.func,
	toDate: PropTypes.string,
	toDateDisabled: PropTypes.bool,
	toTime: PropTypes.string,
	toTimeDisabled: PropTypes.bool,
};

export default TicketDuration;
