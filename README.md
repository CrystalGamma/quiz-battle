# builddom.js – make DOM nodes with the minimal effort possible
This microlibrary allows you to construct DOM nodes in a simple, terse and readable form.

Example:

```js
const myLoginForm = buildDom({'':'form.login',  // the '' key can contain the tag name, id and classes like a CSS selector. The tag name defaults to 'div'.
	c: [{'':'label', c: [	// the 'c' key contains the children of the element. If there should only be one child node, it can be inserted without putting it in an array
		"User name: ",	// strings get converted to text nodes
		{'':'input', name:'username'}	// keys other than '' and 'c' get set as attributes on the element
	]}
});
```

This function was made to allow creating many DOM nodes without having to do glorified string interpolation (AKA ‘templating’):

```js
const comments = [
	{author: "foo", email:"test@example.com", text: "bar"},
	{author: "abc", email:"def@example.com", text: "ghi"}
	// …
];

const commentSection = buildDom({'':'ul.comments', c: comments.map({author, email, text} => {
	'':'li',
	c:[
		{'':'a.commenter', href:'mailto:'+email, c:author},
		": ",
		{'':'p', c: text}
	]
})});
```

# Syntax version
buildDom uses the arrow function (`(…) =>`) and `for (let x of y) …` syntax of ECMAScript 6, as well as the block-scoped variable bindings (`let` and `const`).
If you need to support older browsers, these features should be trivially converted to ES5 syntax by all of the usual ‘transpilers’.

# License
This function and README are released under the [CC0 public domain dedication](http://creativecommons.org/publicdomain/zero/1.0/), which means you can generally use this code as if you wrote it yourself (IANAL, though).
Fixes and enhancements are still welcome though (should be CC0 as well).
