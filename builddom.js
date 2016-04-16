const buildDom = (() =>{
	const tagRegex = new RegExp('^([\\w\\-]+)([\\.#].*)?$');
	const sigilRegex = new RegExp('^([\\.#])([\\w\\-]+)(.*)$');
	return function buildDom(obj) {
		if (obj instanceof Node) {return obj}
		if (Array.isArray(obj)) {return obj.map(buildDom)}
		if (typeof obj === 'string' || obj instanceof String) {return document.createTextNode(obj)}
		let {
			'': selector,
			c: children
		} = obj;
		const match = selector && tagRegex.exec(selector);
		let tag = match ? match[1] : 'div';
		selector = match ? match[2] : '';
		const ids = [], classes = [];
		while (selector) {
			const match = sigilRegex.exec(selector);
			if (!match) {throw "broken selector"}
			const [,sigil, string, rest] = match;
			if (sigil == '#') {
				ids.push(string);
			} else {
				classes.push(string);
			}
			selector = rest;
		}
		const el = document.createElement(tag);
		ids.length && (el.id = ids.join(' '));
		classes.length && (el.className = classes.join(' '));
		for (let attr of Object.keys(obj)) {
			if (attr && attr !== 'c') {
				el.setAttribute(attr, obj[attr]);
			}
		}
		if (!children) {children = []}
		if (!Array.isArray(children)) {children = [children]}
		for (let child of children) {el.appendChild(buildDom(child))}
		return el;
	};
})();

// To the extent possible under law, Jona Stubbe has [waived all copyright and related or neighboring rights](http://creativecommons.org/publicdomain/zero/1.0/) to the buildDom JavaScript function. This work is published from: Germany. 
