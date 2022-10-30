// provide a namespace
if (!window.erp) window.erp = {};

erp.provide = function (namespace) {
	// docs: create a namespace //
	var nsl = namespace.split(".");
	var parent = window;
	for (var i = 0; i < nsl.length; i++) {
		var n = nsl[i];
		if (!parent[n]) {
			parent[n] = {};
		}
		parent = parent[n];
	}
	return parent;
};

erp.provide("frappe.ui.form");

// API globals
window.cur_frm = null;