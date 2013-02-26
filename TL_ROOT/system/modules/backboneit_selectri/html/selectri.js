(function($, $$, undef) {
if(!$) return;

if(!window.bbit) window.bbit = {};
if(!bbit.mt) bbit.mt = {};
if(!bbit.mt.cto) bbit.mt.cto = {};

var Selectri = {},
	TRUE = true,
	OCCLUDE = "bbit.mt.cto.Selectri",
	ATTR_KEY = "data-stri-key",
	reservedAttributeSelectorValueChars = /(["\]])/g,
	escapeAttributeSelectorValue = function(value) { return value.replace(reservedAttributeSelectorValueChars, "\\$1"); },
	events;

Selectri.Implements = [ Options, Class.Occlude ];
Selectri.options = {};

Selectri.initialize = function(container, options, detached) {
	var self = this, occluded = undef, url, loading;
	
	self.container = container = $(container);
	if(!container) return undef;
	if(self.occlude(OCCLUDE, container)) {
		self = self.occluded;
		occluded = TRUE;
	} else {
		self.id = "stri" + String.uniqueID();
	}
	
	if(!occluded && options == TRUE) self.setOptions(JSON.decode(self.container.get("data-stri-options")));
	else if(options) self.setOptions(options);
	
	if(!occluded) {
		self.tree = self.container.getElement(".striTree");
		self.selection = self.container.getElement(".striSelection");
		self.closeTree();
		
		url = window.location.href;
		url += url.indexOf("?") > -1 ? "&" : "?";
		url += "striAction=";
		loading = self.container.addClass.pass("striLoading", self.container);
		self.levelsRequest = new Request.JSON({
			url: url + "levels",
			method: "get",
			link: "chain",
			onRequest: loading,
			onCancel: self.onLevelsCancel,
			onException: self.onLevelsFail,
			onFailure: self.onLevelsFail,
			onSuccess: self.onLevelsSuccess
		});
		self.searchRequest = new Request.JSON({
			url: url + "search",
			method: "get",
			link: "cancel",
			onRequest: loading,
			onCancel: self.onSearchCancel,
			onException: self.onSearchFail,
			onFailure: self.onSearchFail,
			onSuccess: self.onSearchSuccess
		});
		self.sortables = new Sortables(self.selection, {
			
		});
		if(!detached) self.attach();
	}
	
	return self;
};

Selectri.onSelectionDeleteClick = function(event, target) {
	event.preventDefault();
};

Selectri.onSelectionLabelClick = function(event, target) {
	event.preventDefault();
};

Selectri.onTreeLabelClick = function(event, target) {
	event.preventDefault();
	this.toggleNode(target);
};

Selectri.onToggleClick = function(event, target) {
	event.preventDefault();
	this.toggleTree();
};

Selectri.onSearchKeyDown = function(event, target) {};

events = {
	"click:relay(.striSelection .striDelete > .striHandle)":	"onSelectionDeleteClick",
	"click:relay(.striSelection .striLabel > .striHandle)":		"onSelectionLabelClick",
	"click:relay(.striTree .striLabel > .striHandle)":			"onTreeLabelClick",
	"click:relay(.striTools .striToggle)":						"onToggleClick",
	"keydown:relay(.striTools .striSearch):pause(100)":			"onSearchKeyDown"
};

Selectri.attach = function() {
	var self = this;
	self.attached = TRUE;
	Object.each(events, function(handler, event) {
		self.container.addEvent(event, self[handler]);
	});
};
	
Selectri.detach = function() {
	var self = this;
	self.attached = undef;
	Object.each(events, function(handler, event) {
		self.container.removeEvent(event, self[handler]);
	});
};

Selectri.select = function(node) {
	var self = this;
	if(self.isSelected(node)) return;
	node = self.getTreeNode(node);
	if(!node) return;
	node.clone().inject(self.selection);
	node.getElement("input").set("name", self.options.name);
	self.sortables.addItems(node);
};

Selectri.deselect = function(node) {
	var self = this;
	node = self.getSelectionNode(node);
	if(!node) return;
	self.sortables.removeItems(node).destroy();
};

Selectri.isSelected = function(key) {
	return !!this.getSelectionNode(key);
};

Selectri.getKey = function(node) {
	node = $(node);
	if(!node) return undef;
	var key = node.get(ATTR_KEY);
	if(key) return key;
	node = node.getParent(".striNode");
	if(!node) return undef;
	return node.get(ATTR_KEY);
};

Selectri.getNodeSelector = function(key) {
	return ".striNode[" + ATTR_KEY + "=\"" + escapeAttributeSelectorValue(key) + "\"]";
};

Selectri.getSelectionNode = function(key) {
	var self = this;
	if(typeOf(key) != "string" && !(key = self.getKey(key))) return;
	return self.selection.getChildren(self.getNodeSelector(key))[0];
};

Selectri.getTreeNode = function(key) {
	var self = this;
	if(typeOf(key) != "string" && !(key = self.getKey(key))) return;
	return self.tree.getElement("li > " + self.getNodeSelector(key));
};

Selectri.toggleTree = function() {
	var self = this;
	if(self.tree.hasClass("striOpen")) self.closeTree();
	else self.openTree();
};

Selectri.openTree = function() {
	var self = this;
	self.tree.addClass("striOpen");
	if(!self.tree.getChildren().length && !self.levelsRequest.isRunning()) self.levels();
};

Selectri.closeTree = function() {
	var self = this;
	self.tree.removeClass("striOpen");
};

Selectri.toggleNode = function(node) {
	var self = this;
	if(self.getTreeNode(node).getParent("li").hasClass("striOpen")) return self.closeNode(node);
	else self.openNode(node);
};

Selectri.openNode = function(node) {
	var self = this, key = self.getKey(node);
	node = self.getChildrenContainer(node);
	if(!node) return;
	node.getParent("li").addClass("striOpen");
	if(!node.getChildren().length) self.levels(key);
};

Selectri.closeNode = function(node) {
	var self = this;
	node = self.getChildrenContainer(node);
	if(!node) return;
	node.getParent("li").removeClass("striOpen");
};

Selectri.levels = function(start) {
	this.levelsRequest.send({ data: { striStart: start } });
};

Selectri.onLevelsCancel = function() {
	var self = this;
	self.container.removeClass("striLoading").addClass("striError");
};

Selectri.onLevelsFail = function() {
	var self = this;
	self.container.removeClass("striLoading").addClass("striError");
};

Selectri.onLevelsSuccess = function(json) {
	var self = this, node = undef;
	if(!json) return;
	if(json.start && !(node = self.getChildrenContainer(json.start))) return;
	if(!node) node = self.tree;
	node.set("html", json.first);
	
	if(json.levels) json.levels.each(function(level, key) {
		node = self.getChildrenContainer(key);
		if(node && !node.getChildren().length) node.set("html", level);
	});
};

Selectri.getChildrenContainer = function(node) {
	var self = this;
	node = self.getTreeNode(node);
	if(!node) return;
	return node.getParent("li").getChildren(".striChildren")[0];
};

Selectri.search = function(value) {
//	this.searchRequest.send({ data: { key: key } });
};

Selectri.onSearchCancel = function() {
	var self = this;
	self.container.removeClass("striLoading").addClass("striError");
};

Selectri.onSearchFail = function() {
	var self = this;
	self.container.removeClass("striLoading").addClass("striError");
};

Selectri.onSearchSuccess = function(json) {
	
};
	
Selectri.updateRequestToken = function(token) {
	if(!token) return;
	window.REQUEST_TOKEN = token;
	document.getElements("input[type=\"hidden\"][name=\"REQUEST_TOKEN\"]").set("value", token);
};

Selectri.Binds = Object.keys(Selectri).filter(function(method) { return method.substr(0, 2) == "on"; });

Selectri = bbit.mt.cto.Selectri = new Class(Selectri);
Selectri.auto = function() { $$(".striWidget.striAuto").each(function(e) { new Selectri(e); }); };
window.addEvent("domready", Selectri.auto);

})(document.id, window.$$);