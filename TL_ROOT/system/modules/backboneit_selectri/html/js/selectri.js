(function($, $$, undef) {
if(!$) return;

if(!window.bbit) window.bbit = {};
if(!bbit.mt) bbit.mt = {};
if(!bbit.mt.cto) bbit.mt.cto = {};

var Selectri = {},
	TRUE = true,
	EMPTY = Function.from(),
	OCCLUDE = "bbit.mt.cto.Selectri",
	ATTR_KEY = "data-stri-key",
	reservedAttributeSelectorValueChars = /(["\]])/g,
	escapeAttributeSelectorValue = function(value) { return value.replace(reservedAttributeSelectorValueChars, "\\$1"); },
	fixSortables = new Sortables().options.unDraggableTags ? EMPTY : function(sortables, element) { // fuck mootools... or... contao...
		element.store("sortables:start", function(event) {
			if(event.target.get("tag") != "li") event.target = event.target.getParent("li");
			sortables.start(event, element);
		});
	},
	events;

Selectri.Implements = [ Options, Class.Occlude ];
Selectri.options = {};

Selectri.initialize = function(container, options, detached) {
	var self = this, occluded = undef, url;
	
	self.container = container = $(container);
	if(!container) return undef;
	if(self.occlude(OCCLUDE, container)) {
		self = self.occluded;
		occluded = TRUE;
	} else {
		self.id = self.container.get("id") || String.uniqueID();
	}
	
	if(occluded) {
		self.setOptions(options);
	} else {
		if(options === TRUE) options = JSON.decode(self.container.get("data-stri-options"));
		self.setOptions(options);
		self.selection = self.container.getElement(".striSelection > ol");
		self.search = self.container.getElement(".striTools .striSearch input");
		self.value = self.search.get("value");
		self.result = self.container.getElement(".striResult");
		self.tree = self.container.getElement(".striTree");
		
		url = window.location.href + (window.location.href.indexOf("?") > -1 ? "&" : "?");
		url += "striID=" + encodeURIComponent(self.id);
		url += "&striAction=";
		self.toggleRequest = new Request.JSON({ url: url + "toggle", method: "post", link: "chain" });
		delete self.toggleRequest.headers["X-Requested-With"]; // fuck contao...
		self.toggleRequest.addEvent("success", self.onToggleSuccess);
		self.levelsRequest = new Request.JSON({ url: url + "levels", method: "get", link: "chain" });
		self.searchRequest = new Request.JSON({ url: url + "search", method: "get", link: "cancel" });
		"request cancel exception complete failure success".split(" ").each(function(event) {
			self.levelsRequest.addEvent(event, self["onLevels" + event.capitalize()]);
			self.searchRequest.addEvent(event, self["onSearch" + event.capitalize()]);
		});
		
		self.sortables = new Sortables(undef, { opacity: 0.8, onStart: self.onSortStart, onComplete: self.onSortComplete });
		if(self.sortables.options.unDraggableTags) self.sortables.options.unDraggableTags.erase("a");
		else self.selection.getChildren().each(function(element) { fixSortables(self.sortables, element); });
		self.sortables.addLists(self.selection).detach();
		
		if(self.container.hasClass("striOpen")) self.openTree();
		else self.closeTree();
		
		if(!detached) self.attach();
	}
	
	return self;
};

Selectri.onHandleClick				= function(event, target) { event.preventDefault(); };
Selectri.onSelectionMouseDown		= function(event, target) { event.preventDefault(); };
Selectri.onSelectionLabelClick		= function(event, target) { this.openPath(target); this.openTree(); };
Selectri.onSelectionDeselectClick	= function(event, target) { this.deselect(target); };
Selectri.onResultLabelClick			= function(event, target) { this.openPath(target); this.openTree(); };
Selectri.onResultSelectClick		= function(event, target) { this.select(target, TRUE); };
Selectri.onResultDeselectClick		= function(event, target) { this.deselect(target, TRUE); };
Selectri.onTreeLabelClick			= function(event, target) { this.toggleNode(target); };
Selectri.onTreeSelectClick			= function(event, target) { this.select(target, TRUE); };
Selectri.onTreeDeselectClick		= function(event, target) { this.deselect(target, TRUE); };
Selectri.onClearSearchClick			= function(event, target) { this.clearSearch(); };
Selectri.onClearSelectionClick		= function(event, target) { if(event.shift) this.deselectAll(); };
Selectri.onToggleClick				= function(event, target) { this.toggleTree(); };
Selectri.onSearchKeyDown			= function(event, target) { this.requestSearch(target.get("value")); };
Selectri.onSortStart				= function() { this.container.addClass("striSorting"); };
Selectri.onSortComplete				= function() { this.container.removeClass("striSorting"); };
Selectri.onToggleSuccess			= function(json) { if(json && json.token) this.updateRequestToken(json.token); };
Selectri.onLevelsRequest			= function() { this.container.addClass("striLoading"); };
Selectri.onLevelsCancel				= function() { this.container.removeClass("striLoading"); };
Selectri.onLevelsException			= function() { this.container.removeClass("striLoading").addClass("striError"); };
Selectri.onLevelsComplete			= function() { this.container.removeClass("striLoading"); };
Selectri.onLevelsFailure			= function() { };
Selectri.onLevelsSuccess			= function(json) {
	var self = this, node = undef;
	if(!json) return;
	
	if(!self.tree.getChildren().length) {
		if(json.empty) {
			self.container.getFirst(".striMessage").set("text", json.empty);
			self.container.addClass("striEmpty");
			return;
		} else {
			self.tree.set("html", json.first);
		}
	}
	
	if(json.levels) Object.each(json.levels, function(level, key) {
		node = self.getChildrenContainer(key);
		if(!node) return;
		if(!node.getChildren().length) node.set("html", level);
		node.getParent("li").addClass("striOpen");
	});
	
	self.selection.getChildren().each(function(node) {
		node = self.getNode(self.tree, node);
		if(node) node.getParent("li").addClass("striSelected");
	});
};
Selectri.onSearchRequest			= function() { this.container.addClass("striSearching"); };
Selectri.onSearchCancel				= function() { this.container.removeClass("striSearching"); };
Selectri.onSearchException			= function() { this.container.removeClass("striSearching").addClass("striError"); };
Selectri.onSearchComplete			= function() { this.container.removeClass("striSearching"); };
Selectri.onSearchFailure			= function() { this.container.addClass("striNotFound"); };
Selectri.onSearchSuccess			= function(json) {
	var self = this;
	self.result.set("html", json ? json.result : "").addClass("striOpen");
	self.selection.getChildren().each(function(node) {
		node = self.getNode(self.result, node);
		if(node) node.getParent("li").addClass("striSelected");
	});
};

events = {
	"click:relay(.striHandle)":									"onHandleClick",
	"mousedown:relay(.striSelection)":							"onSelectionMouseDown",
	"click:relay(.striSelection .striIcon > .striHandle)":		"onSelectionLabelClick",
	"click:relay(.striSelection .striLabel > .striHandle)":		"onSelectionLabelClick",
	"click:relay(.striSelection .striDeselect > .striHandle)":	"onSelectionDeselectClick",
	"click:relay(.striResult .striIcon > .striHandle)":			"onResultLabelClick",
	"click:relay(.striResult .striLabel > .striHandle)":		"onResultLabelClick",
	"click:relay(.striResult .striSelect > .striHandle)":		"onResultSelectClick",
	"click:relay(.striResult .striDeselect > .striHandle)":		"onResultDeselectClick",
	"click:relay(.striTree .striIcon > .striHandle)":			"onTreeLabelClick",
	"click:relay(.striTree .striLabel > .striHandle)":			"onTreeLabelClick",
	"click:relay(.striTree .striSelect > .striHandle)":			"onTreeSelectClick",
	"click:relay(.striTree .striDeselect > .striHandle)":		"onTreeDeselectClick",
	"click:relay(.striClearSearch > .striHandle)":				"onClearSearchClick",
	"click:relay(.striClearSelection > .striHandle)":			"onClearSelectionClick",
	"click:relay(.striToggle > .striHandle)":					"onToggleClick",
	"keydown:relay(.striSearch > input):pause(250)":			"onSearchKeyDown"
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
	var self = this;
	if(self.isSelected(node)) return;
	
	treeNode = self.getNode(self.tree, node);
	resultNode = self.getNode(self.result, node);
	node = treeNode || resultNode;
	if(!node) return;
	
	node = new Element("li.striSelected").grab(node.clone());
	node.getElement("input").set("name", self.options.name);
	node.getElement(".striSelect").destroy();
	fixSortables(self.sortables, node);
	
	adjustScroll = self.getScrollAdjust(adjustScroll);
	if(self.options.max == 1) self.deselect(self.selection.getFirst());
	node.inject(self.selection);
	self.sortables.addItems(node);
	self.selection.getParent().addClass("striHasSelection");
	adjustScroll();
	
	if(treeNode) treeNode.getParent("li").addClass("striSelected");
	if(resultNode) resultNode.getParent("li").addClass("striSelected");
};

Selectri.deselect = function(node, adjustScroll) {
	var self = this;
	
	node = self.getNode(self.selection, node);
	if(!node) return;

	adjustScroll = self.getScrollAdjust(adjustScroll);
	self.sortables.removeItems(node.getParent("li")).destroy();
	if(!self.selection.getChildren().length) self.selection.getParent().removeClass("striHasSelection");
	adjustScroll();

	node = self.getNode(self.tree, node);
	if(node) node.getParent("li").removeClass("striSelected");

	node = self.getNode(self.result, node);
	if(node) node.getParent("li").removeClass("striSelected");
};

Selectri.deselectAll = function(adjustScroll) {
	var self = this;
	adjustScroll = self.getScrollAdjust(adjustScroll);
	self.selection.getChildren().each(self.deselect, self);
	adjustScroll();
};

Selectri.getScrollAdjust = function(adjust) {
	if(adjust !== TRUE) return EMPTY;
	var self = this;
	scroll = window.getScroll();
	scroll.y -= self.selection.getParent().getSize().y;
	return function() {
		scroll.y += self.selection.getParent().getSize().y;
		window.scrollTo(scroll.x, scroll.y);
	};
};

Selectri.isSelected = function(key) {
	return !!this.getNode(this.selection, key);
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

Selectri.getNode = function(element, key) {
	if(typeOf(key) != "string" && !(key = this.getKey(key))) return;
	return element.getElement(":not(.striPath) > .striNode[" + ATTR_KEY + "=\"" + escapeAttributeSelectorValue(key) + "\"]");
};

Selectri.getChildrenContainer = function(node) {
	var self = this;
	node = self.getNode(self.tree, node);
	if(!node) return;
	return node.getParent("li").getChildren(".striChildren")[0];
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
	node = self.getNode(self.tree, node);
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
	if(node) node.getParents().filter(".striTree li").addClass("striOpen");
	else self.requestPath(key);
};

Selectri.requestLevels = function(start) {
	this.levelsRequest.send({ data: { striStart: start } });
};

Selectri.requestPath = function(key) {
	this.levelsRequest.send({ data: { striAction: "path", striKey: key } });
};

Selectri.requestSearch = function(value) {
	var self = this;
	if(self.value == value) return;
	self.value = value;
	self.closeTree();
	self.container.removeClass("striNotFound");
	self.searchRequest.send({ data: { striSearch: value } });
};

Selectri.clearSearch = function() {
	var self = this;
	self.search.set("value");
	self.container.removeClass("striNotFound");
	self.result.removeClass("striOpen");
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