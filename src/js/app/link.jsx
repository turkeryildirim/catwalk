import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { Link } from 'react-router-dom'
import { connect } from 'react-redux'
import _ from 'lodash'

import app from './app'

class SymfonyLink extends Component {
    render () {
        let childProps = _.omit(this.props, ['route', 'params', 'router', 'dispatch'])
        let params = _.extend({}, this.props.params)
        let path = this.props.path

        if (this.props.to || this.props.href) {
            console.error("Use route parameter with Symfony route ID to link '" + (this.props.to || this.props.href) + "'")
        }

        if(!path) {
            if (this.props.router.hasRoute(this.props.route)) {
                console.log('hatter!');
                path = this.props.router.path(this.props.route, params)
            }
        }

        return (<Link
            to={path || '/__error'}
            {...childProps}>
            {this.props.children}
        </Link>)
    }
}

SymfonyLink.propTypes = {
    router: PropTypes.object.isRequired,
    route: PropTypes.string,
    path: PropTypes.string,
    params: PropTypes.object,
    to: PropTypes.string,
    href: PropTypes.string,
    children: PropTypes.any,
}

SymfonyLink.defaultProps = {
    path: '',
    route: null,
    params: {},
}

export default connect(
    (globalState, props) => {
        return {
            ...props,
            router: app.getRouter(),
        }
    }
)(SymfonyLink)
