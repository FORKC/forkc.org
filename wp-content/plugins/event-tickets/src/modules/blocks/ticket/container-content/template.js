/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Capacity from './capacity/container';
import AdvancedOptions from './advanced-options/container';
import AttendeesRegistration from './attendees-registration/container';
import './style.pcss';

const TicketContainerContent = ( { clientId, hasTicketsPlus } ) => (
	<Fragment>
		<Capacity clientId={ clientId } />
		<AdvancedOptions clientId={ clientId } />
		{ hasTicketsPlus && <AttendeesRegistration clientId={ clientId } /> }
	</Fragment>
);

TicketContainerContent.propTypes = {
	clientId: PropTypes.string.isRequired,
	hasTicketsPlus: PropTypes.bool,
};

export default TicketContainerContent;
