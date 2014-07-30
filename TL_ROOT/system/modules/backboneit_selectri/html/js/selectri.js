(function($, $$, window, undef) {
if(!$) return;
window = $(window);

if(!window.bbit) window.bbit = {};
if(!bbit.mt) bbit.mt = {};
if(!bbit.mt.cto) bbit.mt.cto = {};

var Selectri = {},
	TRUE = true,
	EMPTY = Function.from(),
	OCCLUDE = "bbit.mt.cto.Selectri",
	FN_HL = "bbit.mt.cto.Selectri.hl",
	FN_FADE = "bbit.mt.cto.Selectri.fade",
	ATTR_KEY = "data-stri-key",
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
		var handle = element.getFirst(".striHandle");
		if(!handle) handle = new Element("a.striHandle").set("href", "#").adopt(element.childNodes).inject(element);
		handle.set("title", self.options.openPathTitle);
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
		if(options === TRUE) options = JSON.decode(self.container.get("data-stri-options"));
		self.setOptions(options);
		self.selection = self.container.getElement(".striSelection > ol");
		self.input = self.container.getElement(".striTools .striSearch input");
		self.query = self.input.get("value");
		self.result = self.container.getElement(".striResult");
		self.tree = self.container.getElement(".striTree");
		self.messages = self.container.getElement(".striMessages");

		url = window.location.href + (window.location.href.indexOf("?") > -1 ? "&" : "?");
		url += "striID=" + encodeURIComponent(self.id);
		if(self.options.qs) url += "&" + Object.toQueryString(self.options.qs);
		url += "&striAction=";
		self.toggleRequest = new Request.JSON({ url: url + "toggle", method: "post", link: "chain" });
		delete self.toggleRequest.headers["X-Requested-With"]; // fuck contao...
		self.toggleRequest.addEvent("success", self.onToggleSuccess);
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

		self.sortables = new Sortables(undef, { opacity: 0.8, onStart: self.onSortStart, onComplete: self.onSortComplete, handle: ".striDrag" });
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
Selectri.onPathClick				= function(event, target) { this.openPath(target); this.openTree(); };
Selectri.onClearSearchClick			= function(event, target) { this.clearSearch(); };
Selectri.onClearSelectionClick		= function(event, target) { if(event.shift) this.deselectAll(); };
Selectri.onToggleClick				= function(event, target) { this.toggleTree(); };
Selectri.onSearchKeyDown			= function(event, target) { this.search(target.get("value")); };
Selectri.onSortStart				= function() { this.container.addClass("striSorting"); };
Selectri.onSortComplete				= function() { this.container.removeClass("striSorting"); };
Selectri.onToggleSuccess			= function(json) { if(json && json.token) this.updateRequestToken(json.token); };
Selectri.onLevelsRequest			= function() { this.container.addClass("striLoading"); };
Selectri.onLevelsCancel				= function() { this.container.removeClass("striLoading"); };
Selectri.onLevelsException			= function() { this.container.removeClass("striLoading").addClass("striError"); };
Selectri.onLevelsComplete			= function() { this.container.removeClass("striLoading"); };
Selectri.onLevelsSuccess			= function(json) {
	var self = this, node = undef;
	if(!json) return;

	if(json.token) this.updateRequestToken(json.token);

	self.setMessages(json.messages);

	if(!self.tree.getChildren().length) {
		if(json.empty) {
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

	if(json.action == "path") {
		self.highlight(json.key);
	}
};
Selectri.onSearchRequest			= function() { this.container.addClass("striSearching"); };
Selectri.onSearchCancel				= function() { this.container.removeClass("striSearching"); };
Selectri.onSearchException			= function() { this.container.removeClass("striSearching").addClass("striError"); };
Selectri.onSearchComplete			= function() { this.container.removeClass("striSearching"); };
Selectri.onSearchSuccess			= function(json) {
	var self = this;
	if(!json) return;

	if(json.token) this.updateRequestToken(json.token);

	if(self.query != json.search) return;
	
	self.setMessages(json.messages);

	self.result.set("html", json.result);
	if(self.result.getChildren().length) self.result.addClass("striOpen");
	else self.result.removeClass("striOpen");

	self.selection.getChildren().each(function(node) {
		node = self.getNode(self.result, node);
		if(node) node.getParent("li").addClass("striSelected");
	});
};

events = {
	"click:relay(.striHandle)":											"onHandleClick",
	"mousedown:relay(.striSelection)":									"onSelectionMouseDown",
	"click:relay(.striSelection .striNode > .striLabel > .striHandle)":	"onSelectionLabelClick",
	"click:relay(.striSelection .striDeselect > .striHandle)":			"onSelectionDeselectClick",
	"click:relay(.striResult .striNode > .striLabel > .striHandle)":	"onResultLabelClick",
	"click:relay(.striResult .striSelect > .striHandle)":				"onResultSelectClick",
	"click:relay(.striResult .striDeselect > .striHandle)":				"onResultDeselectClick",
	"click:relay(.striTree .striNode > .striLabel > .striHandle)":		"onTreeLabelClick",
	"click:relay(.striTree .striSelect > .striHandle)":					"onTreeSelectClick",
	"click:relay(.striTree .striDeselect > .striHandle)":				"onTreeDeselectClick",
	"click:relay(.striPathNode > .striLabel > .striHandle)":			"onPathClick",
	"click:relay(.striClearSearch.striHandle)":							"onClearSearchClick",
	"click:relay(.striClearSelection > .striHandle)":					"onClearSelectionClick",
	"click:relay(.striToggle > .striHandle)":							"onToggleClick",
	"keydown:relay(.striSearch > input):pause(250)":					"onSearchKeyDown"
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

Selectri.select = function(node, adjustScroll) {
	var self = this;
	if(self.isSelected(node)) return;

	treeNode = self.getNode(self.tree, node);
	resultNode = self.getNode(self.result, node);
	node = treeNode || resultNode;
	if(!node) return;

	node = node.clone();
	node.getFirst("input").set("name", self.options.name);
	node.getFirst(".striSelect").destroy();
	wrapPathHandle(self, node.getFirst(".striLabel"));
	node = new Element("li.striSelected").grab(node);
	fixSortables(self.sortables, node);

	adjustScroll = self.getScrollAdjust(adjustScroll);
	if(self.options.max == 1) self.deselect(self.selection.getFirst());
	node.inject(self.selection);
	self.sortables.addItems(node);
	self.selection.getParent().addClass("striHasSelection");
	adjustScroll();

	if(treeNode) treeNode.getParent("li").addClass("striSelected");
	if(resultNode) resultNode.getParent("li").addClass("striSelected");
	
	self.fireEvent("selected", self.getKey(node));
};

Selectri.deselect = function(node, adjustScroll) {
	var self = this, selectedNode, removed;

	selectedNode = self.getNode(self.selection, node);
	if(!selectedNode) return;

	adjustScroll = self.getScrollAdjust(adjustScroll);
	removed = self.sortables.removeItems(selectedNode.getParent("li")).dispose();
	if(!self.selection.getChildren().length) self.selection.getParent().removeClass("striHasSelection");
	adjustScroll();

	node = self.getNode(self.tree, selectedNode);
	if(node) node.getParent("li").removeClass("striSelected");

	node = self.getNode(self.result, selectedNode);
	if(node) node.getParent("li").removeClass("striSelected");
	
	self.fireEvent("deselected", self.getKey(selectedNode));
	
	removed.destroy();
};

Selectri.deselectAll = function(adjustScroll) {
	var self = this;
	adjustScroll = self.getScrollAdjust(adjustScroll);
	self.selection.getChildren().each(self.deselect, self);
	adjustScroll();
};

Selectri.getScrollAdjust = function(adjust) {
	if(adjust !== TRUE) return EMPTY;
	var self = this, scroll = window.getScroll();
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

	if(node.get("tag") == "li") key = node.getFirst(".striNode");
	if(!key) key = node.getParent(".striPathNode");
	if(!key) key = node.getParent(".striNode");
	if(!key) return undef;

	return key.get(ATTR_KEY);
};

Selectri.getNode = function(element, key) {
	if(typeOf(key) != "string" && !(key = this.getKey(key))) return;
	return element.getElement(".striNode[" + ATTR_KEY + "=\"" + escapeAttributeSelectorValue(key) + "\"]");
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
	self.tree.getChildren().length || self.levelsRequest.isRunning() || self.levelsRequest.send({ data: self.collectFormData() });
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
	node.getChildren().length || self.levelsRequest.send({ data: self.collectFormData({ striKey: key }) });
	self.toggleRequest.send({ data: self.collectFormData({ striKey: key, striOpen: 1 }) });
};

Selectri.closeNode = function(node) {
	var self = this, key = self.getKey(node);
	if(!key) return;
	node = self.getChildrenContainer(node);
	if(!node) return;
	node.getParent("li").removeClass("striOpen");
	self.toggleRequest.send({ data: self.collectFormData({ striKey: key, striOpen: 0 }) });
};

Selectri.openPath = function(node) {
	var self = this, key = self.getKey(node);
	if(!key) return;
	node = self.getNode(self.tree, key);
	if(!node) {
		self.pathRequest.send({ data: self.collectFormData({ striKey: key }) });
		return;
	}
	node.getParent().getParents().filter(".striTree li").addClass("striOpen");
	self.highlight(key);
};

Selectri.highlight = function(node) {
	node = this.getNode(this.tree, node);
	if(!node) return;
	clearTimeout(node.retrieve(FN_HL));
	clearTimeout(node.retrieve(FN_FADE));
	node.store(FN_HL, node.addClass("striHighlight").removeClass.delay(200, node, "striHighlight"));
	node.store(FN_FADE, node.addClass("striFade").removeClass.delay(3000, node, "striFade"));
};

Selectri.search = function(query) {
	var self = this;
	if(!query || !query.length) return self.clearSearch();
	self.input.addClass("striQuery");
	if(self.query == query) return;
	self.query = query;
	self.closeTree();
	self.container.removeClass("striNotFound");
	self.searchRequest.send({ data: self.collectFormData({ striSearch: query }) });
};

Selectri.clearSearch = function() {
	var self = this;
	self.query = undef;
	self.setMessages(undef);
	self.input.set("value");
	self.input.removeClass("striQuery");
	self.container.removeClass("striNotFound");
	self.result.removeClass("striOpen");
};

Selectri.collectFormData = function(parameters) {
	var data = {};
	for (var index in this.container.form.elements) {
		var element = this.container.form.elements[index];
		if (element.name) {
			data[element.name] = element.value;
		}
	}
	if (parameters) {
		for (var key in parameters) {
			data[key] = parameters[key];
		}
	}
	data["REQUEST_TOKEN"] = this.getRequestToken();
	return data;
};

Selectri.getRequestToken = function() {
	return window.REQUEST_TOKEN || document.getElements("input[type=\"hidden\"][name=\"REQUEST_TOKEN\"]").get("value")[0];
};

Selectri.updateRequestToken = function(token) {
	if(!token) return;
	window.REQUEST_TOKEN = token;
	document.getElements("input[type=\"hidden\"][name=\"REQUEST_TOKEN\"]").set("value", token);
};

Selectri.Binds = Object.keys(Selectri).filter(function(method) { return method.substr(0, 2) == "on"; });

Selectri = bbit.mt.cto.Selectri = new Class(Selectri);
Selectri.scan = function() { $$(".striWidget.striAuto").each(function(e) { new Selectri(e, TRUE); }); };
window.addEvent("domready", Selectri.scan);
window.addEvent("ajaxready", Selectri.scan);

})(document.id, window.$$, window);