const makeXHR = (method, target, headers, callback) => {
	const res = new XMLHttpRequest();
	res.onreadystatechange = () => {if (res.readyState === 4) {callback(res)}};
	res.open(method, target);
	const test = {}.hasOwnProperty.bind(headers);
	for (let header in headers) {if (test(header)){res.setRequestHeader(header, headers[header])}}
	return res;
}
