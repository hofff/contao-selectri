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
	fixSortables = new Sortables().options.unDraggableTags ? Function.from() : function(sortables, element) {
		element.store("sortables:start", function(event) {
			if(event.target.get("tag") != "li") event.target = event.target.getParent("li");
			sortables.start(event, element);
		});
	},
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
		self.selection = self.container.getElement(".striSelection");
		self.result = self.container.getElement(".striResult");
		self.tree = self.container.getElement(".striTree");
		self.value = self.container.getElement(".striTools .striSearch input").get("value");
		self.closeTree();
		
		url = window.location.href;
		url += url.indexOf("?") > -1 ? "&" : "?";
		url += "striAction=";
		loading = self.container.addClass.pass("striLoading", self.container);
		self.toggleRequest = new Request.JSON({ url: url + "toggle", method: "post", link: "chain" });
		delete self.toggleRequest.headers["X-Requested-With"]; // fuck contao...
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
		
		self.sortables = new Sortables(undef, {
			opacity: 0.8,
			onStart: self.onSortStart,
			onComplete: self.onSortComplete
		});
		if(self.sortables.options.unDraggableTags) self.sortables.options.unDraggableTags.erase("a");
		else self.selection.getChildren().each(function(element) { fixSortables(self.sortables, element); });
		self.sortables.addLists(self.selection).detach();
		if(!detached) self.attach();
	}
	
	return self;
};

Selectri.onHandleClick				= function(event) { event.preventDefault(); };
Selectri.onSortStart				= function() { this.container.addClass("striSorting"); };
Selectri.onSortComplete				= function() { this.container.removeClass("striSorting"); };
Selectri.onSelectionLabelClick		= function(event, target) { this.openPath(target); this.openTree(); };
Selectri.onSelectionDeselectClick	= function(event, target) { this.deselect(target); };
Selectri.onSelectionMouseDown		= function(event, target) { event.preventDefault(); };
Selectri.onResultLabelClick			= function(event, target) { this.clearSearch(); this.openPath(target); this.openTree(); };
Selectri.onResultSelectClick		= function(event, target) { this.select(target, TRUE); };
Selectri.onResultDeselectClick		= function(event, target) { this.deselect(target, TRUE); };
Selectri.onTreeLabelClick			= function(event, target) { this.toggleNode(target); };
Selectri.onTreeSelectClick			= function(event, target) { this.select(target, TRUE); };
Selectri.onTreeDeselectClick		= function(event, target) { this.deselect(target, TRUE); };
Selectri.onToggleClick				= function(event, target) { this.toggleTree(); };
Selectri.onSearchKeyDown			= function(event, target) { this.search(target.get("value")); };

events = {
	"click:relay(.striHandle)":									"onHandleClick",
	"click:relay(.striSelection .striLabel > .striHandle)":		"onSelectionLabelClick",
	"click:relay(.striSelection .striDeselect > .striHandle)":	"onSelectionDeselectClick",
	"mousedown:relay(.striSelection)":							"onSelectionMouseDown",
	"click:relay(.striResult .striLabel > .striHandle)":		"onResultLabelClick",
	"click:relay(.striResult .striSelect > .striHandle)":		"onResultSelectClick",
	"click:relay(.striResult .striDeselect > .striHandle)":		"onResultDeselectClick",
	"click:relay(.striTree .striLabel > .striHandle)":			"onTreeLabelClick",
	"click:relay(.striTree .striSelect > .striHandle)":			"onTreeSelectClick",
	"click:relay(.striTree .striDeselect > .striHandle)":		"onTreeDeselectClick",
	"click:relay(.striTools .striToggle)":						"onToggleClick",
	"keydown:relay(.striTools .striSearch input):pause(100)":	"onSearchKeyDown"
};

Selectri.attach = function() {
	var self = this;
	self.attached = TRUE;
	self.sortables.attach();
	Object.each(events, function(handler, event) {
		self.container.addEvent(event, self[handler]);
	});
};
	
Selectri.detach = function() {
	var self = this;
	self.attached = undef;
	self.sortables.detach();
	Object.each(events, function(handler, event) {
		self.container.removeEvent(event, self[handler]);
	});
};

Selectri.select = function(node, adjustScroll) {
	var self = this, scroll;
	if(self.isSelected(node)) return;
	
	node = self.getTreeNode(node);
	if(!node) return;
	
	scroll = window.getScroll();
	scroll.y -= self.selection.getSize().y;

	if(self.options.max == 1) self.deselect(self.selection.getFirst());
	
	node.addClass(".striSelected");
	node = new Element("li").grab(node.clone()).inject(self.selection);
	node.getElement("input").set("name", self.options.name);
	node.getElement(".striSelect").destroy();
	fixSortables(self.sortables, node);
	self.sortables.addItems(node);
	
	scroll.y += self.selection.getSize().y;
	if(adjustScroll) window.scrollTo(scroll.x, scroll.y);
};

Selectri.deselect = function(node, adjustScroll) {
	var self = this, scroll, treeNode;
	
	node = self.getSelectionNode(node);
	if(!node) return;
	
	treeNode = self.getTreeNode(node);
	if(treeNode) treeNode.removeClass(".striSelected");
	
	scroll = window.getScroll();
	scroll.y -= self.selection.getSize().y;
	
	self.sortables.removeItems(node.getParent("li")).destroy();
	
	scroll.y += self.selection.getSize().y;
	if(adjustScroll) window.scrollTo(scroll.x, scroll.y);
};

Selectri.isSelected = function(key) {
	return !!this.getSelectionNode(key);
};

Selectri.getKey = function(node) {
	node = $(node);
	if(!node) return undef;
	
	var key = node.get(ATTR_KEY);
	if(key) return key;
	
	node = node.get("tag") == "li" ? node.getFirst(".striNode") : node.getParent(".striNode");
	if(!node) return undef;
	
	return node.get(ATTR_KEY);
};

Selectri.getNodeSelector = function(key) {
	return ":not(.striPath) > .striNode[" + ATTR_KEY + "=\"" + escapeAttributeSelectorValue(key) + "\"]";
};

Selectri.getSelectionNode = function(key) {
	var self = this;
	if(typeOf(key) != "string" && !(key = self.getKey(key))) return;
	return self.selection.getElement(self.getNodeSelector(key));
};

Selectri.getTreeNode = function(key) {
	var self = this;
	if(typeOf(key) != "string" && !(key = self.getKey(key))) return;
	return self.tree.getElement(self.getNodeSelector(key));
};

Selectri.getResultNode = function(key) {
	var self = this;
	if(typeOf(key) != "string" && !(key = self.getKey(key))) return;
	return self.result.getElement(self.getNodeSelector(key));
};

Selectri.toggleTree = function() {
	var self = this;
	if(self.container.hasClass("striOpen")) self.closeTree();
	else self.openTree();
};

Selectri.openTree = function() {
	var self = this;
	self.container.addClass("striOpen");
	self.clearSearch();
	if(!self.tree.getChildren().length && !self.levelsRequest.isRunning()) self.requestLevels();
};

Selectri.closeTree = function() {
	this.container.removeClass("striOpen");
};

Selectri.toggleNode = function(node) {
	var self = this;
	node = self.getTreeNode(node);
	if(!node) return;
	if(node.getParent("li").hasClass("striOpen")) return self.closeNode(node);
	else self.openNode(node);
};

Selectri.openNode = function(node) {
	var self = this, key = self.getKey(node);
	if(!key) return;
	node = self.getChildrenContainer(node);
	if(!node) return;
	node.getParent("li").addClass("striOpen");
	if(!node.getChildren().length) self.requestLevels(key);
	self.toggleRequest.send({ data: { striKey: key, striOpen: 1, REQUEST_TOKEN: self.getRequestToken() } });
};

Selectri.closeNode = function(node) {
	var self = this, key = self.getKey(node);
	if(!key) return;
	node = self.getChildrenContainer(node);
	if(!node) return;
	node.getParent("li").removeClass("striOpen");
	self.toggleRequest.send({ data: { striKey: key, striOpen: 0, REQUEST_TOKEN: self.getRequestToken() } });
};

Selectri.openPath = function(node) {
	var self = this, key = self.getKey(node);
	if(!key) return;
	node = self.getChildrenContainer(node);
	if(node) node.getParents(".striTree li").addClass("striOpen");
	else self.requestPath(key);
};

Selectri.requestLevels = function(start) {
	this.levelsRequest.send({ data: { striStart: start } });
};

Selectri.requestPath = function(key) {
	this.levelsRequest.send({ data: { striAction: "path", striKey: key } });
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
	
	if(json.first && !self.tree.getChildren().length) self.tree.set("html", json.first);
	
	if(json.levels) Object.each(json.levels, function(level, key) {
		node = self.getChildrenContainer(key);
		if(!node) return;
		if(!node.getChildren().length) node.set("html", level);
		node.getParent("li").addClass("striOpen");
	});
	
	self.selection.getChildren().each(function(node) {
		node = self.getTreeNode(node);
		if(node) node.addClass("striSelected");
	});
};

Selectri.getChildrenContainer = function(node) {
	var self = this;
	node = self.getTreeNode(node);
	if(!node) return;
	return node.getParent("li").getChildren(".striChildren")[0];
};

Selectri.clearSearch = function() {
	var self = this;
	self.container.getElement(".striTools .striSearch input").set("value");
	self.result.removeClass("striOpen");
};

Selectri.search = function(value) {
	var self = this;
	if(self.value == value) return;
	self.value = value;
	self.closeTree();
	self.searchRequest.send({ data: { striSearch: value } });
};

Selectri.onSearchCancel = function() {
	this.container.removeClass("striLoading");
};

Selectri.onSearchFail = function() {
	this.container.removeClass("striLoading").addClass("striError");
};

Selectri.onSearchSuccess = function(json) {
	var self = this;
	self.result.set("html", json ? json.result : "").addClass("striOpen");
	self.selection.getChildren().each(function(node) {
		node = self.getResultNode(node);
		if(node) node.addClass("striSelected");
	});
};

Selectri.getRequestToken = function() {
	return window.REQUEST_TOKEN || document.getElements("input[type=\"hidden\"][name=\"REQUEST_TOKEN\"]").get("value");
};

Selectri.updateRequestToken = function(token) {
	if(!token) return;
	window.REQUEST_TOKEN = token;
	document.getElements("input[type=\"hidden\"][name=\"REQUEST_TOKEN\"]").set("value", token);
};

Selectri.Binds = Object.keys(Selectri).filter(function(method) { return method.substr(0, 2) == "on"; });

Selectri = bbit.mt.cto.Selectri = new Class(Selectri);
Selectri.auto = function() { $$(".striWidget.striAuto").each(function(e) { new Selectri(e, TRUE); }); };
window.addEvent("domready", Selectri.auto);

})(document.id, window.$$);