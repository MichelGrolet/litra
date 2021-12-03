import React, { Component } from 'react';
import '../styles/header.scss';
import { Link } from 'react-router-dom';

class Header extends Component {
	render() {
		return (
			<div id="header">
				<h1><Link to="/">MVW</Link></h1>
				<Link to="/">notre offre</Link>
				<Link className="cta" to="/inscription">Rejoindre MVW</Link>
			</div>
		);
	}
}

export default Header;