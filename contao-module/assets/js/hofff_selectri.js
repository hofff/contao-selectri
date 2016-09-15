(function($, $$, window, undef) {
if(!$) return;
window = $(window);

if(!window.Hofff) window.Hofff = {};

var Selectri = {},
	TRUE = true,
	EMPTY = Function.from(),
	OCCLUDE = "hofff_selectri",
	FN_HL = "hofff_selectri_highlight",
	FN_FADE = "hofff_selectri_fade",
	ATTR_KEY = "data-hofff-selectri-key",
	reservedAttributeSelectorValueChars = /(["\]])/g,
	escapeAttributeSelectorValue = function(value) { return value.replace(reservedAttributeSelectorValueChars, "\\$1"); },
	fixSortables = new Sortables([], {}).options.unDraggableTags
		? EMPTY
		: function(sortables, element) { // fuck mootools... or... contao...
			element.store("sortables:start", function(event) {
				if(event.target.get("tag") != "li") event.target = event.target.getParent("li");
				sortables.start(event, element);
			});
		},
	wrapPathHandle = function(self, element) {
		var handle = element.getFirst(".hofff-selectri-handle");
		if(!handle) handle = new Element("a.hofff-selectri-handle").set("href", "#").adopt(element.childNodes).inject(element);
		handle.set("title", self.options.openPathTitle);
	},
	createViewportStabilizer = function(subject) {
		if(!subject) return EMPTY;
		var scroll = window.getScroll();
		scroll.y -= subject.getPosition().y;
		return function() {
			scroll.y += subject.getPosition().y;
			window.scrollTo(scroll.x, scroll.y);
		};
	},
	stabilizeViewport = function(handler) {
		return function(event, target) {
			var stabilizer = createViewportStabilizer(target.getParent().getParent());
			handler.apply(this, arguments);
			stabilizer();
		};
	},
	events;

Selectri.Implements = [ Options, Class.Occlude, Events ];
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
		if(options === TRUE) options = JSON.decode(self.container.get("data-hofff-selectri-options"));
		self.setOptions(options);
		self.selection = self.container.getElement(".hofff-selectri-selection > ol");
		self.input = self.container.getElement(".hofff-selectri-tools .hofff-selectri-search input");
		if(self.input) self.query = self.input.get("value");
		self.result = self.container.getElement(".hofff-selectri-result");
		self.suggestions = self.container.getElement(".hofff-selectri-suggestions");
		self.tree = self.container.getElement(".hofff-selectri-tree");
		self.toggleContentHandle = self.container.getElement(".hofff-selectri-toggle-content > .hofff-selectri-handle");
		self.sources = $$([ self.result, self.suggestions, self.tree ].clean());
		self.messages = self.container.getElement(".hofff-selectri-messages");

		url = window.location.href + (window.location.href.indexOf("?") > -1 ? "&" : "?");
		url += "hofff_selectri_field=" + encodeURIComponent(self.id);
		if(self.options.qs) url += "&" + Object.toQueryString(self.options.qs);
		url += "&hofff_selectri_action=";
		self.toggleRequest = new Request.JSON({ url: url + "toggle", method: "post", link: "chain" });
		delete self.toggleRequest.headers["X-Requested-With"]; // fuck contao...
		self.levelsRequest = new Request.JSON({ url: url + "levels", method: "post", link: "chain" });
		delete self.levelsRequest.headers["X-Requested-With"]; // fuck contao...
		self.pathRequest = new Request.JSON({ url: url + "path", method: "post", link: "chain" });
		delete self.pathRequest.headers["X-Requested-With"]; // fuck contao...
		self.searchRequest = new Request.JSON({ url: url + "search", method: "post", link: "cancel" });
		delete self.searchRequest.headers["X-Requested-With"]; // fuck contao...
		"request cancel exception complete success".split(" ").each(function(event) {
			self.levelsRequest.addEvent(event, self["onLevels" + event.capitalize()]);
			self.pathRequest.addEvent(event, self["onLevels" + event.capitalize()]);
			self.searchRequest.addEvent(event, self["onSearch" + event.capitalize()]);
		});

		self.sortables = new Sortables(undef, { opacity: 0.8, onStart: self.onSortStart, onComplete: self.onSortComplete, handle: ".hofff-selectri-drag" });
		if(self.sortables.options.unDraggableTags) self.sortables.options.unDraggableTags.erase("a");
		else self.selection.getChildren().each(function(element) { fixSortables(self.sortables, element); });
		self.sortables.addLists(self.selection).detach();

		if(self.suggestions) {
			self.selection.getChildren().each(function(node) {
				node = self.getNode(self.suggestions, node);
				if(node) node.getParent("li").addClass("hofff-selectri-selected");
			});
		}

		if(self.isTreeOpen()) self.openTree();
		else { self.openSuggestions(); self.closeTree(); }

		if(!detached) self.attach();
	}

	return self;
};

Selectri.onHandleClick				= function(event, target) { event.preventDefault(); };
Selectri.onSelectionLabelClick		= function(event, target) { this.openPath(target); this.openTree(); };
Selectri.onSelectionDeselectClick	= function(event, target) { this.deselect(target); };
Selectri.onResultLabelClick			= function(event, target) { this.openPath(target); this.openTree(); };
Selectri.onResultSelectClick		= stabilizeViewport(function(event, target) { this.select(target); });
Selectri.onResultDeselectClick		= stabilizeViewport(function(event, target) { this.deselect(target); });
Selectri.onSuggestionsLabelClick	= function(event, target) { this.openPath(target); this.openTree(); };
Selectri.onSuggestionsSelectClick	= stabilizeViewport(function(event, target) { this.select(target); });
Selectri.onSuggestionsDeselectClick	= stabilizeViewport(function(event, target) { this.deselect(target); });
Selectri.onTreeLabelClick			= function(event, target) { this.toggleNode(target); };
Selectri.onTreeSelectClick			= stabilizeViewport(function(event, target) { this.select(target); });
Selectri.onTreeDeselectClick		= stabilizeViewport(function(event, target) { this.deselect(target); });
Selectri.onPathClick				= function(event, target) { this.openPath(target); this.openTree(); };
Selectri.onClearSearchClick			= function(event, target) { this.clearSearch(); this.openSuggestions(); };
Selectri.onClearSelectionClick		= stabilizeViewport(function(event, target) { if(event.shift) this.deselectAll(); });
Selectri.onToggleClick				= function(event, target) { this.toggleTree(); if(!this.isTreeOpen()) this.openSuggestions(); };
Selectri.onToggleContentClick		= stabilizeViewport(function(event, target) { this.toggleContent(); });
Selectri.onSearchKeyDown			= function(event, target) {
	target = target.get("value");
	if(target && target.length) this.search(target);
	else this.openSuggestions();
};
Selectri.onSortStart				= function() { this.container.addClass("hofff-selectri-sorting"); };
Selectri.onSortComplete				= function() { this.container.removeClass("hofff-selectri-sorting"); };
Selectri.onLevelsRequest			= function() { this.container.addClass("hofff-selectri-loading"); };
Selectri.onLevelsCancel				= function() { this.container.removeClass("hofff-selectri-loading"); };
Selectri.onLevelsException			= function() { this.container.removeClass("hofff-selectri-loading").addClass("hofff-selectri-error"); };
Selectri.onLevelsComplete			= function() { this.container.removeClass("hofff-selectri-loading"); };
Selectri.onLevelsSuccess			= function(json) {
	var self = this, node = undef;
	if(!json) return;

	self.setMessages(json.messages);

	if(!self.tree.getChildren().length) {
		if(json.empty) {
			self.tree.addClass("hofff-selectri-empty");
			return;
		} else {
			self.tree.set("html", json.first);
		}
	}

	if(json.levels) Object.each(json.levels, function(level, key) {
		node = self.getChildrenContainer(key);
		if(!node) return;
		if(!node.getChildren().length) node.set("html", level);
		node.getParent("li").addClass("hofff-selectri-open");
	});

	self.selection.getChildren().each(function(node) {
		node = self.getNode(self.tree, node);
		if(node) node.getParent("li").addClass("hofff-selectri-selected");
	});

	if(json.action == "path") {
		self.highlight(json.key);
	}
};
Selectri.onSearchRequest			= function() { this.container.addClass("hofff-selectri-searching"); };
Selectri.onSearchCancel				= function() { this.container.removeClass("hofff-selectri-searching"); };
Selectri.onSearchException			= function() { this.container.removeClass("hofff-selectri-searching").addClass("hofff-selectri-error"); };
Selectri.onSearchComplete			= function() { this.container.removeClass("hofff-selectri-searching"); };
Selectri.onSearchSuccess			= function(json) {
	var self = this;
	if(!json) return;

	if(self.query != json.search) return;

	self.setMessages(json.messages);

	self.result.set("html", json.result);
	if(self.result.getChildren().length) self.result.addClass("hofff-selectri-open");
	else self.result.removeClass("hofff-selectri-open");

	self.selection.getChildren().each(function(node) {
		node = self.getNode(self.result, node);
		if(node) node.getParent("li").addClass("hofff-selectri-selected");
	});
};

events = {
	"click:relay(.hofff-selectri-handle)":																				"onHandleClick",
	"click:relay(.hofff-selectri-selection .hofff-selectri-node > .hofff-selectri-label > .hofff-selectri-handle)":		"onSelectionLabelClick",
	"click:relay(.hofff-selectri-selection .hofff-selectri-deselect > .hofff-selectri-handle)":							"onSelectionDeselectClick",
	"click:relay(.hofff-selectri-result .hofff-selectri-node > .hofff-selectri-label > .hofff-selectri-handle)":		"onResultLabelClick",
	"click:relay(.hofff-selectri-result .hofff-selectri-select > .hofff-selectri-handle)":								"onResultSelectClick",
	"click:relay(.hofff-selectri-result .hofff-selectri-deselect > .hofff-selectri-handle)":							"onResultDeselectClick",
	"click:relay(.hofff-selectri-suggestions .hofff-selectri-node > .hofff-selectri-label > .hofff-selectri-handle)":	"onSuggestionsLabelClick",
	"click:relay(.hofff-selectri-suggestions .hofff-selectri-select > .hofff-selectri-handle)":							"onSuggestionsSelectClick",
	"click:relay(.hofff-selectri-suggestions .hofff-selectri-deselect > .hofff-selectri-handle)":						"onSuggestionsDeselectClick",
	"click:relay(.hofff-selectri-tree .hofff-selectri-node > .hofff-selectri-label > .hofff-selectri-handle)":			"onTreeLabelClick",
	"click:relay(.hofff-selectri-tree .hofff-selectri-select > .hofff-selectri-handle)":								"onTreeSelectClick",
	"click:relay(.hofff-selectri-tree .hofff-selectri-deselect > .hofff-selectri-handle)":								"onTreeDeselectClick",
	"click:relay(.hofff-selectri-path-node > .hofff-selectri-label > .hofff-selectri-handle)":							"onPathClick",
	"click:relay(.hofff-selectri-clear-search.hofff-selectri-handle)":													"onClearSearchClick",
	"click:relay(.hofff-selectri-clear-selection > .hofff-selectri-handle)":											"onClearSelectionClick",
	"click:relay(.hofff-selectri-toggle > .hofff-selectri-handle)":														"onToggleClick",
	"click:relay(.hofff-selectri-toggle-content > .hofff-selectri-handle)":												"onToggleContentClick",
	"keydown:relay(.hofff-selectri-search > input):pause(250)":															"onSearchKeyDown"
};

Selectri.attach = function() {
	var self = this;
	self.attached = TRUE;
	self.sortables.attach();
	Object.each(events, function(handler, event) {
		self.container.addEvent(event, self[handler]);
	});

	self.fireEvent("attached");
};

Selectri.detach = function() {
	var self = this;
	self.attached = undef;
	self.sortables.detach();
	Object.each(events, function(handler, event) {
		self.container.removeEvent(event, self[handler]);
	});

	self.fireEvent("detached");
};

Selectri.setMessages = function(messages) {
	var self = this;
	self.messages.empty();

	type = typeOf(messages);
	if(type == "string") messages = [ messages ];
	else if(type != "array") return;

	messages.each(function(message) {
		new Element("p").set("text", message).inject(self.messages);
	});
};

Selectri.select = function(node) {
	var self = this;
	if(self.isSelected(node)) return;

	nodes = $$(self.getNode(self.sources, node).clean());
	if(!nodes.length) return;
	node = nodes[0];

	node = node.clone();
	node.getFirst("input").set("name", self.options.name);
	node.getFirst(".hofff-selectri-select").destroy();
	wrapPathHandle(self, node.getFirst(".hofff-selectri-label"));
	node = new Element("li.hofff-selectri-selected").grab(node);
	fixSortables(self.sortables, node);

	if(self.options.max == 1) self.deselect(self.selection.getFirst());
	node.inject(self.selection);
	self.sortables.addItems(node);
	self.selection.getParent().addClass("hofff-selectri-has-selection");

	nodes.getParent("li").addClass("hofff-selectri-selected");

	self.fireEvent("selected", self.getKey(node));
};

Selectri.deselect = function(node) {
	var self = this, selectedNode, removed;

	selectedNode = self.getNode(self.selection, node);
	if(!selectedNode) return;

	removed = self.sortables.removeItems(selectedNode.getParent("li")).dispose();
	if(!self.selection.getChildren().length) self.selection.getParent().removeClass("hofff-selectri-has-selection");

	$$(self.getNode(self.sources, node).clean()).getParent("li").removeClass("hofff-selectri-selected");

	self.fireEvent("deselected", self.getKey(selectedNode));

	removed.destroy();
};

Selectri.deselectAll = function() {
	this.selection.getChildren().each(this.deselect, this);
};

Selectri.isSelected = function(key) {
	return !!this.getNode(this.selection, key);
};

Selectri.getKey = function(node) {
	node = $(node);
	if(!node) return undef;

	var key = node.get(ATTR_KEY);
	if(key) return key;

	if(node.get("tag") == "li") key = node.getFirst(".hofff-selectri-node");
	if(!key) key = node.getParent(".hofff-selectri-path-node");
	if(!key) key = node.getParent(".hofff-selectri-node");
	if(!key) return undef;

	return key.get(ATTR_KEY);
};

Selectri.getNode = function(element, key) {
	if(typeOf(key) != "string" && !(key = this.getKey(key))) return;
	return element.getElement(".hofff-selectri-node[" + ATTR_KEY + "=\"" + escapeAttributeSelectorValue(key) + "\"]");
};

Selectri.getChildrenContainer = function(node) {
	var self = this;
	if(!self.tree) return;
	node = self.getNode(self.tree, node);
	if(!node) return;
	return node.getParent("li").getChildren(".hofff-selectri-children")[0];
};

Selectri.isTreeOpen = function() {
	return this.tree && this.tree.hasClass("hofff-selectri-open");
};

Selectri.toggleTree = function() {
	this.isTreeOpen() ? this.closeTree() : this.openTree();
};

Selectri.openTree = function() {
	var self = this;
	if(!self.tree) return;
	self.tree.addClass("hofff-selectri-open");
	self.clearSearch();
	self.closeSuggestions();
	self.tree.getChildren().length || self.levelsRequest.isRunning() || self.levelsRequest.send({ data: self.buildPOSTData() });
};

Selectri.closeTree = function() {
	if(this.tree) this.tree.removeClass("hofff-selectri-open");
};

Selectri.isContentVisible = function() {
	return !this.container.hasClass("hofff-selectri-hide-content");
};

Selectri.toggleContent = function() {
	this.isContentVisible() ? this.hideContent() : this.showContent();
};

Selectri.showContent = function() {
	this.container.removeClass("hofff-selectri-hide-content");
	var handle = this.toggleContentHandle;
	if(handle) handle.set("text", handle.get("data-hofff-selectri-hide"));
};

Selectri.hideContent = function() {
	this.container.addClass("hofff-selectri-hide-content");
	var handle = this.toggleContentHandle;
	if(handle) handle.set("text", handle.get("data-hofff-selectri-show"));
};

Selectri.toggleNode = function(node) {
	var self = this;
	if(!self.tree) return;
	node = self.getNode(self.tree, node);
	if(!node) return;
	if(node.getParent("li").hasClass("hofff-selectri-open")) return self.closeNode(node);
	else self.openNode(node);
};

Selectri.openNode = function(node) {
	var self = this, key = self.getKey(node);
	if(!self.tree || !key) return;
	node = self.getChildrenContainer(node);
	if(!node) return;
	node.getParent("li").addClass("hofff-selectri-open");
	node.getChildren().length || self.levelsRequest.send({ data: self.buildPOSTData({ hofff_selectri_key: key }) });
	self.toggleRequest.send({ data: self.buildPOSTData({ hofff_selectri_key: key, hofff_selectri_open: 1 }) });
};

Selectri.closeNode = function(node) {
	var self = this, key = self.getKey(node);
	if(!self.tree || !key) return;
	node = self.getChildrenContainer(node);
	if(!node) return;
	node.getParent("li").removeClass("hofff-selectri-open");
	self.toggleRequest.send({ data: self.buildPOSTData({ hofff_selectri_key: key, hofff_selectri_open: 0 }) });
};

Selectri.openPath = function(node) {
	var self = this, key = self.getKey(node);
	if(!self.tree || !key) return;
	node = self.getNode(self.tree, key);
	if(!node) {
		self.pathRequest.send({ data: self.buildPOSTData({ hofff_selectri_key: key }) });
		return;
	}
	node.getParent().getParents().filter(".hofff-selectri-tree li").addClass("hofff-selectri-open");
	self.highlight(key);
};

Selectri.highlight = function(node) {
	if(!this.tree) return;
	node = this.getNode(this.tree, node);
	if(!node) return;
	clearTimeout(node.retrieve(FN_HL));
	clearTimeout(node.retrieve(FN_FADE));
	node.store(FN_HL, node.addClass("hofff-selectri-highlight").removeClass.delay(200, node, "hofff-selectri-highlight"));
	node.store(FN_FADE, node.addClass("hofff-selectri-fade").removeClass.delay(3000, node, "hofff-selectri-fade"));
};

Selectri.search = function(query) {
	var self = this;
	if(!self.result || !query || !query.length) return self.clearSearch();
	self.input.addClass("hofff-selectri-query");
	if(self.query == query) return;
	self.query = query;
	self.closeTree();
	self.closeSuggestions();
	self.container.removeClass("hofff-selectri-not-found");
	self.searchRequest.send({ data: self.buildPOSTData({ hofff_selectri_search: query }) });
};

Selectri.clearSearch = function() {
	var self = this;
	if(!self.result) return;
	self.query = undef;
	self.setMessages(undef);
	self.input.set("value");
	self.input.removeClass("hofff-selectri-query");
	self.container.removeClass("hofff-selectri-not-found");
	self.result.removeClass("hofff-selectri-open");
};

Selectri.openSuggestions = function() {
	var self = this;
	if(!self.suggestions || !self.suggestions.getElement("li")) return;
	self.closeTree();
	self.clearSearch();
	self.suggestions.addClass("hofff-selectri-open");
};

Selectri.closeSuggestions = function() {
	if(this.suggestions) this.suggestions.removeClass("hofff-selectri-open");
};

Selectri.buildPOSTData = function(params) {
	return $(this.container.form).toQueryString() + Object.toQueryString(params);
};

Selectri.Binds = Object.keys(Selectri).filter(function(method) { return method.substr(0, 2) == "on"; });

Selectri = window.Hofff.Selectri = new Class(Selectri);
Selectri.scan = function() { $$(".hofff-selectri-widget.hofff-selectri-auto").each(function(e) { new Selectri(e, TRUE); }); };
window.addEvent("domready", Selectri.scan);
window.addEvent("ajaxready", Selectri.scan);

})(document.id, window.$$, window);