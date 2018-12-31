/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { plugins } from '@moderntribe/common/data';
import { withSaveData, withStore } from '@moderntribe/common/hoc';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import {
	isModalShowing,
	getModalTicketId,
} from '@moderntribe/tickets/data/shared/move/selectors';

const mapStateToProps = ( state, ownProps ) => {
	const props = { blockId: ownProps.clientId };

	return {
		blockId: ownProps.clientId,
		hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
		hasBeenCreated: selectors.getTicketHasBeenCreated( state, props ),
		isDisabled: selectors.isTicketDisabled( state, props ),
		isLoading: selectors.getTicketIsLoading( state, props ),
		ticketId: selectors.getTicketId( state, props ),
		isModalShowing: isModalShowing( state ),
		modalTicketId: getModalTicketId( state ),
	};
};

const mapDispatchToProps = ( dispatch, ownProps ) => {
	const { clientId } = ownProps;

	return {
		onBlockUpdate: ( isSelected ) => (
			dispatch( actions.setTicketIsSelected( clientId, isSelected ) )
		),
		setInitialState: ( props ) => {
			dispatch( actions.registerTicketBlock( clientId ) );
			dispatch( actions.setTicketInitialState( props ) );
		},
	};
};

const mergeProps = ( stateProps, dispatchProps, ownProps ) => ( {
	...stateProps,
	...dispatchProps,
	...ownProps,
	isModalShowing: stateProps.isModalShowing && stateProps.modalTicketId === stateProps.ticketId,
} );

export default compose(
	withStore( { isolated: true } ),
	connect(
		mapStateToProps,
		mapDispatchToProps,
		mergeProps,
	),
	withSaveData(),
)( Template );

