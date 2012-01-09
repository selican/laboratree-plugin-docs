/*jslint bitwise: true, browser: true, continue: true, unparam: true, rhino: true, sloppy: true, eqeq: true, sub: false, vars: true, white: true, plusplus: true, maxerr: 150, indent: 4 */
/*global laboratree: false, Ext: false */
laboratree.docs = {};
laboratree.docs.dashboard = {};
laboratree.docs.type = {};
laboratree.docs.doc = {};
laboratree.docs.masks = {};
laboratree.docs.view = {};

/* Plugin Functions */
/* Dashboard Functions */
laboratree.docs.makePortlet = function(data_url) {
	laboratree.docs.portlet = new laboratree.docs.Portlet(data_url);
}

laboratree.docs.Portlet = function(data_url) {
	Ext.QuickTips.init();

	Ext.state.Manager.setProvider(new Ext.state.CookieProvider({
		expires: new Date(new Date().getTime() + (1000 * 60 * 60 * 24 * 7))
	}));

	this.data_url = data_url;

	this.state_id = 'state-' + laboratree.context.table_type + '-dashboard-' + laboratree.context.table_id;

	this.column = 'dashboard-column-left';
	this.position = 1;

	this.store = new Ext.tree.TreeLoader({
		url: data_url,
		baseParams: {
			model: 'docs',
		},
		uiProviders: {
			'col': Ext.tree.TreeNodeUI
		},

		listeners: {
			beforeload: function(store, options) {
				laboratree.docs.masks.portlet = new Ext.LoadMask('portlet-documents-' + laboratree.context.table_type + '-' + laboratree.context.table_id, {
					msg: 'Loading...'
				});
				laboratree.docs.masks.portlet.show();
			},
			load: function(store, records, options) {
				laboratree.docs.masks.portlet.hide();
			}
		}
	});

	this.portlet = new Ext.tree.TreePanel({
		id: 'portlet-documents-' + laboratree.context.table_type + '-' + laboratree.context.table_id,
		height: 200,

		reparent: laboratree.links.docs.reparent + '.json',
		reorder: laboratree.links.docs.reorder + '.json',

		rootVisible: false,
		autoScroll: true,
		animate: false,
		containerScroll: true,
		lines: true,

		enableDD: true,

		stateEvents: ['collapsenode', 'expandnode', 'movenode', 'nodedrop', 'insert'],
		stateful: true,
		getState: function() {
			var nodes = [];
			this.getRootNode().eachChild(function(child) {
				var storeTreeState = function(node, expandNodes) {
					if(node.isExpanded() && node.childNodes.length > 0) {
						expandNodes.push(node.getPath());
						node.eachChild(function(child) {
							storeTreeState(child, expandNodes);
						});
					}
				};
				storeTreeState(child, nodes);
			});

			return {
				expandedNodes: nodes
			};
		},
		applyState: function(state) {
			var that = this;
			this.getLoader().on('load', function() {
				var cookie = Ext.state.Manager.get('portlet-documents-' + laboratree.context.table_type + '-' + laboratree.context.table_id);
				if(cookie) {
					var nodes = cookie.expandedNodes;
					var i;
					for(i = 0; i < nodes.length; i++) {
						if(typeof nodes[i] != 'undefined') {
							that.expandPath(nodes[i]);
						}
					}
				}
			});
		},

		contextMenu: new Ext.menu.Menu({
			items: [{
				id: 'checkin-document',
				text: 'Check In'
			},{
				id: 'checkout-document',
				text: 'Check Out'
			},{
				id: 'cancel-checkout-document',
				text: 'Cancel Checkout'
			},{
				id: 'download',
				text: 'Download'
			},{
				id: 'add-document',
				text: 'Add Document'
			},{
				id: 'add-folder',
				text: 'Add Folder'
			},{
				id: 'edit-document',
				text: 'Edit'
			},{
				id: 'delete-node',
				text: 'Delete'
			}],
			listeners: {
				itemclick: function(item) {
					var node = item.parentMenu.contextNode;
					var node_id = node.attributes.id;
					var treepanel = node.getOwnerTree();
					var panel_id = treepanel.id;
					var parent_id = node.parentNode.attributes.id;

					switch(item.id) {
						case 'checkout-document':
							Ext.Ajax.request({
								url: String.format(laboratree.links.docs.checkout, node_id) + '.json',
								success: function(response, request) {
									var data = Ext.decode(response.responseText);
									if(!data) {
										request.failure(response, request);
										return;
									}

									if(!data.success) {
										request.failure(response, request);
										return;
									}

									window.location = String.format(laboratree.links.docs.download, node_id, '');

									var treeloader = treepanel.getLoader();
									var parentnode = node.parentNode;
									if(parentnode)
									{
										treeloader.load(parentnode, function() {
											parentnode.expand();
										});
									}
								},
								failure: function(response, request) {

								},
								scope: this
							});
							break;
						case 'checkin-document':
							laboratree.docs.makeCheckIn(node_id, panel_id, parent_id);
							break;
						case 'cancel-checkout-document':
							laboratree.docs.cancel_checkout(panel_id, node_id);
							break;
						case 'download':
							window.location = String.format(laboratree.links.docs.download, node_id, '');
							break;
						case 'edit-document':
							laboratree.docs.makeEdit(node_id, panel_id, parent_id);
							break;
						case 'delete-node':
							laboratree.docs.deleteDoc(panel_id, node_id);
							break;
						case 'add-document':
							laboratree.docs.makeDocument(node.attributes.table_type, node.attributes.table_id, panel_id, node_id);
							break;
						case 'add-folder':
							laboratree.docs.makeFolder(panel_id, node_id);
							break;
					}
				}
			}
		}),

		loader: this.store,

		root: new Ext.tree.AsyncTreeNode({
			text: 'Root',
			expanded: true
		}),

		listeners: {
			contextmenu: function(node, e) {
				node.select();

				var c = node.getOwnerTree().contextMenu;

				c.items.each(function(item, index, length) {
					item.hide();
					return true;
				});

				if(node.leaf)
				{
					c.items.get('download').show();
					c.items.get('edit-document').show();
					c.items.get('delete-node').show();

					if(node.attributes.status == 'Checked In')
					{
						c.items.get('checkout-document').show();
					}
					else
					{
						c.items.get('checkin-document').show();
						c.items.get('cancel-checkout-document').show();
					}
				}
				else
				{
					c.items.get('add-document').show();
					c.items.get('add-folder').show();

					if(node.draggable)
					{
						c.items.get('delete-node').show();
					}
				}

				c.contextNode = node;
				c.showAt(e.getXY());
			},
			startdrag: function(tree, node, e) {
				this.oldPosition = node.parentNode.indexOf(node);
				this.oldNextSibling = node.nextSibling;
			},
			movenode: function(tree, node, oldParent, newParent, position) {
				var url = tree.reparent;

				var params = {
					node: node.id,
					parent: newParent.id,
					position: position
				};

				if(oldParent == newParent) {
					url = tree.reorder;

					params = {
						node: node.id,
						delta: (position-this.oldPosition)
					};
				}

				tree.disable();

				Ext.Ajax.request({
					url: url,
					params: params,
					success: function(response, request) {
						var data = Ext.decode(response.responseText);
						if(data.errors) {
							request.failure();
						}
						else if(data.success) {
							tree.enable();
						}
						else {
							tree.enable();
						}
					},
					failure: function() {
						tree.suspendEvents();
						oldParent.appendChild(node);
						if(this.oldNextSibling) {
							oldParent.insertBefore(node, this.oldNextSibling);
						}

						tree.resumeEvents();
						tree.enable();
					}
				});
			},
			click: function(node, checked) {
				if(node && node.leaf) {
					window.location = String.format(laboratree.links.docs.view, node.attributes.id);
				}
			}
		}
	});

	this.panel = {
		id: 'panel-documents',
		title: 'Documents',
		layout: 'fit',

		tools: [{
			id: 'help',
			qtip: 'Help Documents',
			handler: function(event, toolEl, panel, tc) {
				Ext.Ajax.request({
					url: String.format(laboratree.links.help.site.index, laboratree.context.table_type, 'documents') + '.json',
					success: function(response, request) {
						var data = Ext.decode(response.responseText);
						if(data.success) {
							laboratree.helpPopup('Documents Help', data.help.Help.content);
						}
					},
					failure: function() {
					}
				});
			}
		}],

		items: this.portlet,

		listeners: {
			expand: function(p) {
				laboratree.docs.portlet.toggle(false);
			},
			collapse: function(p) {
				laboratree.docs.portlet.toggle(true);
			}
		}
	};

	if(laboratree.site.permissions.docs.view & laboratree.context.permissions.doc) {
		this.panel.title = '<a href="' + String.format(laboratree.links.docs[laboratree.context.table_type], laboratree.context.table_id, '') + '">Documents</a>';

		this.panel.tools.unshift({
			id: 'restore',
			qtip: 'Documents Dashboard',
			handler: function() {
				window.location = String.format(laboratree.links.docs[laboratree.context.table_type], laboratree.context.table_id);
			}
		});
	}

	var states = Ext.state.Manager.get(this.state_id, null);
	if(!states) {
		states = {};
	}

	var state = states.docs;
	if(!state) {
		state = {
			collapsed: false,
			column: this.column,
			position: this.position
		};
	}

	this.panel.collapsed = state.collapsed;

	var column = Ext.getCmp(state.column);
	if(!column) {
		return false;
	}

	column.insert(state.position, this.panel);
};

laboratree.docs.Portlet.prototype.toggle = function(collapsed) {
	var states = Ext.state.Manager.get(this.state_id, null);
	if(!states) {
		states = {};
	}

	var state = states.docs;
	if(!state) {
		state = {
			collapsed: false,
			column: this.column,
			position: this.position
		};

		states.docs = state;
	}

	states.docs.collapsed = collapsed;

	Ext.state.Manager.set(this.state_id, states);
};

laboratree.docs.TypeWindow = Ext.extend(Ext.Window, {
	row_id: null,
	type_id: null,
	type_name: null,
	fields: [],
	subtypes: [],
	stores: {},

	constructor: function(config) {
		config = config || {};

		Ext.apply(this, config);

		laboratree.docs.TypeWindow.superclass.constructor.call(this, config);
	}
});

laboratree.docs.TypeRecord = Ext.data.Record.create([
	'id',
	'title',
	'data'
]);

laboratree.docs.makeVersions = function(div, data_url) {
	laboratree.docs.versions = new laboratree.docs.Versions(div, data_url);
};

laboratree.docs.Versions = function(div, data_url) {
	Ext.QuickTips.init();

	this.div = div;
	this.data_url = data_url;

	this.store = new Ext.data.JsonStore({
		root: 'versions',
		autoLoad: true,
		url: data_url,
		fields: [
			'id', 'doc_id', 'version', 'size', 'revised', 'author', 'author_id', 'changelog', 'revision'
		]
	});

	this.store.setDefaultSort('version', 'DESC');

	this.grid = new Ext.grid.GridPanel({
		id: 'versions',
		title: 'Document Versions',
		renderTo: div,
		width: '100%',
		height: 600,
		stripeRows: true,
		loadMask: {msg: 'Loading...'},

		store: this.store,

		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: true
			},
			columns: [{
				id: 'version',
				header: 'Version',
				dataIndex: 'version',
				width: 75,
				align: 'center',
				renderer: laboratree.docs.render.versions.version
			},{
				id: 'size',
				header: 'Size',
				dataIndex: 'size',
				width: 100,
				renderer: laboratree.docs.render.versions.size
			},{
				id: 'revised',
				header: 'Revised',
				dataIndex: 'revised',
				width: 150
			},{
				id: 'author',
				header: 'Author',
				dataIndex: 'author_id',
				width: 225,
				renderer: laboratree.docs.render.versions.author
			},{
				id: 'changelog',
				header: 'Changes',
				dataIndex: 'changelog',
				width: 75,
				align: 'center',
				renderer: laboratree.docs.render.versions.changelog
			},{
				id: 'signature',
				header: 'Signature',
				dataIndex: 'id',
				width: 75,
				renderer: laboratree.docs.render.versions.signature
			}]
		}),

		bbar: new Ext.PagingToolbar({
			pageSize: 30,
			store: this.store,
			displayInfo: true,
			displayMsg: 'Displaying version {0} - {1} of {2}',
			emptyMsg: 'No versions to display'
		})
	});
};

laboratree.docs.makeView = function(div, url_id, data_url) {
	laboratree.docs.view = new laboratree.docs.View(div, url_id, data_url);
};

laboratree.docs.View = function(div, url_id, data_url) {
	this.div = div;
	this.url_id = url_id;
	Ext.QuickTips.init();

	this.fields = new Ext.data.JsonStore({
		root: 'fields',
		baseParams: {
			action: 'fields'
		},
		url: data_url,
		fields: [
			'id', 'name', 'required', 'type', 'type_id', 'value', 'display'
		]
	});

	Ext.Ajax.request({
		url: data_url,
		params: {
			action: 'doc'
		},
		success: function(response, request) {
			var data = Ext.decode(response.responseText);
			if(!data)
			{
				request.failure(response, request);
				return;
			}
			if(data.error || data.errors)
			{
				request.failure(response, request);
				return;
			}

			var panelConfig = {
				id: 'doc-view',
				renderTo: this.div,
				frame: true,
				width: '100%',
				title: data.title,
				autoHeight: true,
				anchor: '100%',
				store: this.store,

				items: [{
					flex: 1,
					anchor: '100% 100%',
					frame: true,

					items: [{
						html: '<div><span class="docTitle">Author: </span><a href="' + String.format(laboratree.links.users.profile, data.table_id) +'">' + data.author + '</a></div>'
					},{
						html: '<div><span class="docTitle">Created: </span>' + data.created + '</div>'
					},{
						html: '<div><span class="docTitle">Status: </span>' + data.status + '</div>'
					},{
						html: '<div><span class="docTitle">Version: </span><a href="' + String.format(laboratree.links.docs.versions, data.id) + '">' + data.version + '</a></div>'
					},{
						html: '<div><span class="docTitle">Signature: </span>' + data.checksum + '</div><br />'
					},{
						html: '<div><span class="docTitle">Description: </span>' + data.description + '</div>'
					},{
						title: 'Document Types',

						flex: 1,

						layout: 'hbox',
						layoutConfig: {
							pack: 'start',
							align: 'stretch'
						},

						height: 300,

						items: [{
							xtype: 'treepanel',
							id: 'type-tree-' + data.id,
							rootVisible: false,
							autoScroll: true,

							frame: true,
							bodyStyle: 'background-color: #FFFFFF',

							flex: 1,

							animate: false,
							containerScroll: true,

							stateEvents: ['collapsenode', 'expandnode'],
							stateful: true,
							getState: function() {
								var nodes = [];
								this.getRootNode().eachChild(function(child) {
									var storeTreeState = function(node, expandNodes) {
										if(node.isExpanded() && node.childNodes.length > 0) {
											expandNodes.push(node.getPath());
											node.eachChild(function(child) {
												storeTreeState(child, expandNodes);
											});
										}
									};
									storeTreeState(child, nodes);
								});

								return {
									expandedNodes: nodes
								};
							},
							applyState: function(state) {
								var that = this;
								this.getLoader().on('load', function() {
									var cookie = Ext.state.Manager.get(that.id);
									var nodes = cookie.expandedNodes;
									var i = 0;

									for(i = 0; i < nodes.length; i++) {
										if(typeof nodes[i] != 'undefined') {
											that.expandPath(nodes[i]);
										}
									}
								});
							},

							lines: true,

							loader: new Ext.tree.TreeLoader({
								tree_id: 'type-tree-' + data.id,
								dataUrl: data_url,
								baseParams: {
									action: 'types'
								},

								listeners: {
									beforeload: function(loader, node, callback) {
										laboratree.docs.masks.view = new Ext.LoadMask(loader.tree_id, {
											msg: 'Loading...'
										});
										laboratree.docs.masks.view.show();
									},
									load: function(store, records, options) {
										laboratree.docs.masks.view.hide();
									}
								}
							}),

							root: new Ext.tree.AsyncTreeNode({
								text: 'Document Types',
								allowDrop: false,
								draggable: false
							}),

							listeners: {
								click: function(node, e) {
									laboratree.docs.view.fields.load({
										params: {
											row_id: node.attributes.id
										},
										scope: this
									});

									return true;
								}
							}
						},{
							id: 'type-fields',
							xtype: 'grid',

							flex: 1,

							store: this.fields,

							cm: new Ext.grid.ColumnModel({
								defaults: {
									sortable: false
								},

								columns: [{
									id: 'field',
									header: 'Field',
									dataIndex: 'name',
									width: 100
								},{
									id: 'value',
									header: 'Value',
									dataIndex: 'value'
								}]
							})
						}]
					}]
				}],

				buttons: [{
					text: 'Download',
					handler: function(){
						window.location = String.format(laboratree.links.docs.download, laboratree.context.doc_id, '');
					}
				}]
			};

			if(laboratree.site.permissions.docs.edit & laboratree.context.permissions.document) {
				panelConfig.buttons.push({
					text: 'Edit',
					handler: function() {
						window.location = String.format(laboratree.links.docs.edit, laboratree.context.doc_id);
					}
				});
			}

			if(laboratree.site.permissions.docs['delete'] & laboratree.context.permissions.document) {
				panelConfig.buttons.push({
					text: 'Delete',
					handler: function() {
						if(window.confirm('Are you sure?')) {
							Ext.Ajax.request({
								url: String.format(laboratree.links.docs['delete'], laboratree.context.doc_id) + '.json',
								success: function(response, request) {
									var data = Ext.decode(response.responseText);
									if(!data) {
										request.failure(response, request);
										return;
									}

									if(!data.success) {
										request.failure(response, request);
										return;
									}

									window.location = String.format(laboratree.links.docs[laboratree.context.table_type], laboratree.context.table_id);
								},
								failure: function(response, request) {

								},
								scope: this
							});
						}
					}
				});
			}

			this.panel = new Ext.Panel(panelConfig);
			this.panel.doLayout();
		},
		failure: function(response, request) {

		},
		scope: this
	});
};

/*
 * Document List for Users/Groups/Projects
 */
laboratree.docs.makeDashboard = function(div) {
	laboratree.docs.dashboard = new laboratree.docs.Dashboard(div);
};

laboratree.docs.makeTree = function(title, table_type, table_id, shared) {
	var data_url = String.format(laboratree.links.docs[table_type], table_id, shared) + '.json';
	var reparent_url = laboratree.links.docs.reparent + '.json';
	var reorder_url = laboratree.links.docs.reorder + '.json';
	var panel_id = table_type + ':' + table_id + ':' + shared;

	var tree = new laboratree.docs.Tree(title, data_url, reparent_url, reorder_url, panel_id);
};

laboratree.docs.makeFolder = function(panel_id, parent_id) {
	Ext.MessageBox.prompt('Folder Name', 'Please enter a folder name:', function(btn, folder) {
		if(btn == 'ok') {
			Ext.Ajax.request({
				url: String.format(laboratree.links.docs.folder, parent_id) + '.json',
				params: {
					folder: folder
				},
				success: function(response, request) {
					var data = Ext.decode(response.responseText);
					if(!data) {
						request.failure(response, request);
						return;
					}

					if(!data.success) {
						request.failure(response, request);
						return;
					}

					var treepanel = Ext.getCmp(panel_id);
					if(!treepanel) {
						request.failure(response, request);
					}

					var treeloader = treepanel.getLoader();
					var parentnode = treepanel.getNodeById(parent_id);
					if(parentnode) {
						treeloader.load(parentnode, function() {
							parentnode.expand();
						}, this);
					}
				},
				failure: function(response, request) {

				},
				scope: this
			});
		}
	});
};

laboratree.docs.editFolder = function(panel_id, folder_id) {
	var treePanel = Ext.getCmp(panel_id);
	if(!treePanel) {
		return false;
	}

	var treeLoader = treePanel.getLoader();
	if(!treeLoader) {
		return false;
	}

	var folderNode = treePanel.getNodeById(folder_id);
	if(!folderNode) {
		return false;
	}

	var parentNode = folderNode.parentNode;
	if(!parentNode) {
		return false;
	}

	var parent_id = parentNode.attributes.id;

	Ext.MessageBox.prompt('Folder Name', 'Please enter a folder name:', function(btn, folder) {
		if(btn == 'ok') {
			Ext.Ajax.request({
				url: String.format(laboratree.links.docs.folder, parent_id) + '.json',
				params: {
					id: folder_id,
					folder: folder
				},
				success: function(response, request) {
					var data = Ext.decode(response.responseText);
					if(!data) {
						request.failure(response, request);
						return;
					}

					if(!data.success) {
						request.failure(response, request);
						return;
					}

					treeLoader.load(parentNode, function() {
						parentNode.expand();
					}, this);
				},
				failure: function(response, request) {

				},
				scope: this
			});
		}
	}, this, false, folderNode.text);
};


laboratree.docs.makeDocument = function(table_type, table_id, panel_id, parent_id) {
	var data_url = String.format(laboratree.links.docs.add, table_type, table_id, parent_id) + '.extjs';
	laboratree.docs.doc = new laboratree.docs.Doc(table_type, table_id, panel_id, parent_id, data_url);
};

laboratree.docs.deleteDoc = function(panel_id, node_id) {
	Ext.MessageBox.confirm('Delete Document/Folder', 'Are you sure?', function(btn) {
		if(btn == 'yes') {
			Ext.Ajax.request({
				url: String.format(laboratree.links.docs.del, node_id) + '.json',
				success: function(response, request) {
					var treepanel = Ext.getCmp(panel_id);
					if(!treepanel) {
						request.failure(response, request);
						return;
					}

					var treeloader = treepanel.getLoader();
					if(!treeloader) {
						request.failure(response, request);
						return;
					}

					var node = treepanel.getNodeById(node_id);
					if(node) {
						node.remove();

						var details = Ext.getCmp('details-panel');
						if(details) {
							details.body.update(laboratree.docs.dashboard.details);
						}
					}
				},
				failure: function(response, request) {

				},
				scope: this
			});
		}
	});
};

laboratree.docs.checkout = function(panel_id, node_id) {
	var treepanel = Ext.getCmp(panel_id);
	if(!treepanel) {
		window.location = String.format(laboratree.links.docs.checkbox, node_id);
		return;
	}

	var treeloader = treepanel.getLoader();
	var node = treepanel.getNodeById(node_id);
	if(!node) {
		window.location = String.format(laboratree.links.docs.checkout, node_id);
		return;
	}

	var parentnode = node.parentNode;
	if(!parentnode)
	{
		window.location = String.format(laboratree.links.docs.checkout, node_id);
		return;
	}

	Ext.Ajax.request({
		url: String.format(laboratree.links.docs.checkout, node_id) + '.json',
		success: function(response, request) {
			var data = Ext.decode(response.responseText);

			if(!data.success) {
				request.failure(response, request);
				return;
			}

			treeloader.load(parentnode, function() {
				parentnode.expand();

				var details = Ext.getCmp('details-panel');
				if(details) {
					laboratree.docs.dashboard.templates.file.checkedout.overwrite(details.body, node.attributes);
				}

				window.location = String.format(laboratree.links.docs.download, node_id, '');
			});
		},
		failure: function(response, request) {

		},
		scope: this
	});

};

laboratree.docs.cancel_checkout = function(panel_id, node_id) {
	Ext.MessageBox.confirm('Cancel Checkout', 'Are you sure?', function(btn) {
		if(btn == 'yes') {
			Ext.Ajax.request({
				url: String.format(laboratree.links.docs.cancel_checkout, node_id) + '.json',
				success: function(response, request) {
					//TODO: Check for success
					var treepanel = Ext.getCmp(panel_id);
					if(!treepanel) {
						request.failure();
						return;
					}

					var treeloader = treepanel.getLoader();
					var node = treepanel.getNodeById(node_id);
					if(node) {
						var parentnode = node.parentNode;
						if(parentnode)
						{
							treeloader.load(parentnode, function() {
								parentnode.expand();

								var details = Ext.getCmp('details-panel');
								if(details) {
									laboratree.docs.dashboard.templates.file.checkedin.overwrite(details.body, node.attributes);
								}
							});
						}
					}
				},
				failure: function(response, request) {

				}
			});
		}
	});
};

laboratree.docs.Dashboard = function(div) {
	Ext.state.Manager.setProvider(new Ext.state.CookieProvider({
		expires: new Date(new Date().getTime() + (1000 * 60 * 60 * 24 * 7))
	}));

	Ext.QuickTips.init();

	this.details = '<div class="doc_details"><i>Select a document or folder to see more information...</i></div>';

	this.hbox = new Ext.Panel({
		id: 'dashboard',
		layout: 'hbox',
		renderTo: div,
		width: '100%',
		height: 674,
		border: false,

		items: [{
			id: 'tree-box',
			layout: 'vbox',
			height: 674,
			width: '60%',
			border: false,
			flex:1
		},{
			id: 'details-panel',
			title: 'Document Details',
			flex: 1,
			width: '40%',
			height: 674,
			autoScroll: true,
			html: this.details,
			bodyStyle: 'padding: 5px; font-size: 1.2em;'
		}]
	});

	/*
	 * Templates
	 */
	var checkedin = [
		'<div class="doc_details">',
		'<h2 class="doc-panel-title"><a href="' + String.format(laboratree.links.docs.view, '{id}') + '">{title}</a></h2>',
		'<table>',
		'<tr><td><b>Author</b>:</td><td>{author}</td></tr>',
		'<tr><td><b>Created</b>:</td><td>{created}</td></tr>',
		'<tr><td><b>Status</b>:</td><td>{status}</td></tr>',
		'<tr><td><b>Version</b>:</td><td><a href="' + String.format(laboratree.links.docs.versions, '{id}') + '">{version}</a></td></tr>',
		'<tr><td><b>Size</b>:</td><td>{size}</td></tr>',
		'<tr><td><b>Signature</b>:</td><td><a href="' + String.format(laboratree.links.docs.checksum, '{id}') + '">SHA-1</a></td></tr>',
		'<tr><td colspan="2"><b>Description</b>:</td></tr>',
		'<tr><td colspan="2">{description}</td></tr>',
		'</table>',
		'<br />'
	];

	var checkedout = [
		'<div class="doc_details">',
		'<h2 class="doc-panel-title"><a href="' + String.format(laboratree.links.docs.view, '{id}') + '">{title}</a></h2>',
		'<table>',
		'<tr><td><b>Author</b>:</td><td>{author}</td></tr>',
		'<tr><td><b>Created</b>:</td><td>{created}</td></tr>',
		'<tr><td><b>Status</b>:</td><td>{status}</td></tr>',
		'<tr><td><b>Version</b>:</td><td><a href="' + String.format(laboratree.links.docs.versions, '{id}') + '">{version}</a></td></tr>',
		'<tr><td><b>Size</b>:</td><td>{size}</td></tr>',
		'<tr><td><b>Signature</b>:</td><td><a href="' + String.format(laboratree.links.docs.checksum, '{id}') + '">SHA-1</a></td></tr>',
		'<tr><td colspan="2"><b>Description</b>:</td></tr>',
		'<tr><td colspan="2">{description}</td></tr>',
		'</table>',
		'<br />'
	];

	var folder = [
		'<div class="doc_details">'
	];

	var root = [
		'<div class="doc_details">'
	];

	if(laboratree.site.permissions.docs.add & laboratree.context.permissions.document) {
		folder.push('<div><a href="#" onclick="laboratree.docs.makeDocument(\'{table_type}\', \'{table_id}\', \'{panel_id}\', {id}); return false;" title="Add Document to {title}">Add Document to \'{title}\'</a></div>');
		folder.push('<div><a href="javascript:laboratree.docs.makeFolder(\'{panel_id}\', {id});" title="Add Folder to {title}">Add Folder to \'{title}\'</a></div>');

		root.push('<div><a href="#" onclick="laboratree.docs.makeDocument(\'{table_type}\', \'{table_id}\', \'{panel_id}\', {id}); return false;" title="Add Document to {title}">Add Document to \'{title}\'</a></div>');
		root.push('<div><a href="#" onclick="laboratree.docs.makeFolder(\'{panel_id}\', {id}); return false;" title="Add Folder to {title}">Add Folder to \'{title}\'</a></div>');
	}

	if(laboratree.site.permissions.docs.checkout & laboratree.context.permissions.document) {
		checkedin.push('<div><a href="#" onclick="laboratree.docs.checkout(\'{panel_id}\', \'{id}\'); return false;" title="Check Out {title}">Check Out \'{title}\'</a></div>');
	}

	if(laboratree.site.permissions.docs.checkin & laboratree.context.permisisons.document) {
		checkedout.push('<div><a href="#" onclick="laboratree.docs.makeCheckIn(\'{id}\', \'{panel_id}\'); return false;" title="Check In {title}">Check In \'{title}\'</a></div>');
	}

	if(laboratree.site.permissions.docs.cancel_checkout & laboratree.context.permissions.document) {
		checkedout.push('<div><a href="#" onclick="laboratree.docs.cancel_checkout(\'{panel_id}\', \'{id}\'); return false;" title="Cancel Checkout">Cancel Checkout</a></div>');
	}

	checkedin.push('<div><a href="' + String.format(laboratree.links.docs.download, '{id}', '') + '" title="Download {title}">Download \'{title}\'</a></div>');
	checkedout.push('<div><a href="' + String.format(laboratree.links.docs.download, '{id}', '') + '" title="Download {title}">Download \'{title}\'</a></div>');

	if(laboratree.site.permissions.docs.edit & laboratree.context.permissions.document) {
		checkedin.push('<div><a href="#" onclick="laboratree.docs.makeEdit(\'{id}\', \'{panel_id}\'); return false;" title="Edit {title}">Edit \'{title}\'</a></div>');
		checkedout.push('<div><a href="#" onclick="laboratree.docs.makeEdit(\'{id}\', \'{panel_id}\'); return false;" title="Edit {title}">Edit \'{title}\'</a></div>');
		folder.push('<div><a href="#" onclick="laboratree.docs.editFolder(\'{panel_id}\', {id}); return false;" title="Edit Folder">Edit Folder</a></div>');
	}

	if(laboratree.site.permissions.docs['delete'] & laboratree.context.permissions.document) {
		checkedin.push('<div><a href="#" onclick="laboratree.docs.deleteDoc(\'{panel_id}\', \'{id}\'); return false;" title="Delete {title}">Delete \'{title}\'</a></div>');
		checkedout.push('<div><a href="#" onclick="laboratree.docs.deleteDoc(\'{panel_id}\', {id}); return false;" title="Delete {title}">Delete \'{title}\'</a></div>');
		folder.push('<div><a href="#" onclick="laboratree.docs.deleteDoc(\'{panel_id}\', {id}); return false;" title="Delete {title}">Delete \'{title}\'</a></div>');
	}

	checkedin.push('</div>');
	checkedout.push('</div>');
	folder.push('</div>');
	root.push('</div>');

	this.templates = {
		file: {
			checkedin: new Ext.Template(checkedin),
			checkedout: new Ext.Template(checkedout)
		},
		folder: new Ext.Template(folder),
		root: new Ext.Template(root)
	};

	/* Context Menu */
	this.treeContextMenu = new Ext.menu.Menu({
		items: [{
			id: 'checkin-document',
			text: 'Check In'
		},{
			id: 'checkout-document',
			text: 'Check Out'
		},{
			id: 'cancel-checkout-document',
			text: 'Cancel Checkout'
		},{
			id: 'download',
			text: 'Download'
		},{
			id: 'add-document',
			text: 'Add Document'
		},{
			id: 'add-folder',
			text: 'Add Folder'
		},{
			id: 'edit-folder',
			text: 'Edit Folder'
		},{
			id: 'edit-document',
			text: 'Edit'
		},{
			id: 'delete-node',
			text: 'Delete'
		}],
		listeners: {
			itemclick: function(item) {
				var node = item.parentMenu.contextNode;
				var node_id = node.attributes.id;
				var panel_id = node.getOwnerTree().id;
				var parent_id = node.parentNode.attributes.id;

				switch(item.id) {
					case 'checkout-document':
						Ext.Ajax.request({
							url: String.format(laboratree.links.docs.checkout, node_id) + '.json',
							success: function(response, request) {
								var data = Ext.decode(response.responseText);

								if(data.errors) {
									request.failure();
									return;
								}

								window.location = String.format(laboratree.links.docs.download, node_id, '');

								var treepanel = Ext.getCmp(panel_id);
								if(!treepanel)
								{
									return;
								}

								var treeloader = treepanel.getLoader();
								var parentnode = node.parentNode;
								if(parentnode)
								{
									treeloader.load(parentnode, function() {
										parentnode.expand();

										var details = Ext.getCmp('details-panel');
										if(details) {
											laboratree.docs.dashboard.templates.file.checkedout.overwrite(details.body, node.attributes);
										}
									});
								}
							},
							failure: function(response, request) {

							}
						});
						break;
					case 'checkin-document':
						laboratree.docs.makeCheckIn(node_id, panel_id, parent_id);
						break;
					case 'cancel-checkout-document':
						laboratree.docs.cancel_checkout(panel_id, node_id);
						break;
					case 'download':
						window.location = String.format(laboratree.links.docs.download, node_id, '');
						break;
					case 'edit-document':
						laboratree.docs.makeEdit(node_id, panel_id, parent_id);
						break;
					case 'delete-node':
						laboratree.docs.deleteDoc(panel_id, node_id);
						break;
					case 'add-document':
						laboratree.docs.makeDocument(node.attributes.table_type, node.attributes.table_id, panel_id, node_id);
						break;
					case 'add-folder':
						laboratree.docs.makeFolder(panel_id, node_id);
						break;
					case 'edit-folder':
						laboratree.docs.editFolder(panel_id, node_id);
						break;
				}
			}
		}
	});
};

laboratree.docs.Dashboard.prototype.add = function(component)
{
	var treebox = Ext.getCmp('tree-box');
	treebox.add(component);
	treebox.doLayout();
};

laboratree.docs.Tree = function(title, data_url, reparent_url, reorder_url, panel_id) {
	this.treepanel = new Ext.ux.tree.ColumnTree({
		id: panel_id,
		rootVisible: false,
		autoScroll: true,

		columns: [{
			header: 'Title',
			width: 260,
			dataIndex: 'title'
		},{
			header: 'Author',
			width: 120,
			dataIndex: 'author'
		},{
			header: 'Status',
			width: 100,
			dataIndex: 'status'
		},{
			header: 'Size',
			width: 80,
			dataIndex: 'size'
		}],

		animate: false,
		enableDD: true,
		containerScroll: true,

		title: title,
		flex: 1,
		width: '100%',
		height: 337,

		stateEvents: ['collapsenode', 'expandnode', 'movenode', 'nodedrop', 'insert'],
		stateful: true,
		getState: function() {
			var nodes = [];
			this.getRootNode().eachChild(function(child) {
				var storeTreeState = function(node, expandNodes) {
					if(node.isExpanded() && node.childNodes.length > 0) {
						expandNodes.push(node.getPath());
						node.eachChild(function(child) {
							storeTreeState(child, expandNodes);
						});
					}
				};
				storeTreeState(child, nodes);
			});

			return {
				expandedNodes: nodes
			};
		},
		applyState: function(state) {
			var that = this;
			this.getLoader().on('load', function() {
				var cookie = Ext.state.Manager.get(that.id);
				var nodes = cookie.expandedNodes;
				var i = 0;

				for(i = 0; i < nodes.length; i++) {
					if(typeof nodes[i] != 'undefined') {
						that.expandPath(nodes[i]);
					}
				}
			});
		},

		tools: [{
			id: 'up',
			qtip: 'Collapse Document Tree',
			handler: function(event, toolEl, panel, tc) {
				panel.collapseAll();
			}
		},{
			id: 'down',
			qtip: 'Expand Document Tree',
			handler: function(event, toolEl, panel, tc) {
				panel.expandAll();
			}
		},{
			id: 'refresh',
			qtip: 'Refresh Document Tree',
			handler: function(event, toolEl, panel, tc) {
				var treeloader = panel.getLoader();
				var rootnode = panel.getRootNode();
				treeloader.load(rootnode, function() {
					rootnode.expand();
				});
			}
		}],

		lines: true,

		contextMenu: laboratree.docs.dashboard.treeContextMenu,

		loader: new Ext.tree.TreeLoader({
			dataUrl: data_url,
			uiProviders: {
				'col': Ext.ux.tree.ColumnNodeUI
			},

			listeners: {
				beforeload: function(store, options) {
					laboratree.docs.masks[panel_id] = new Ext.LoadMask(panel_id, {
						msg: 'Loading...'
					});
					laboratree.docs.masks[panel_id].show();
				},
				load: function(store, records, options) {
					laboratree.docs.masks[panel_id].hide();
				}
			}
		}),

		root: new Ext.tree.AsyncTreeNode({
			text: title,
			allowDrop: false,
			draggable: false
		}),

		listeners: {
			contextmenu: function(node, e) {
				node.select();

				var c = node.getOwnerTree().contextMenu;

				c.items.each(function(item, index, length) {
					item.hide();
					return true;
				});

				if(node.leaf)
				{
					if(laboratree.site.permissions.docs.view & laboratree.context.permissions.document) {
						c.items.get('download').show();
					}

					if(laboratree.site.permissions.docs.edit & laboratree.context.permissions.document) {
						c.items.get('edit-document').show();
					}

					if(laboratree.site.permissions.docs['delete'] & laboratree.context.permissions.document) {
						c.items.get('delete-node').show();
					}

					if(node.attributes.status == 'Checked In')
					{
						if(laboratree.site.permissions.docs.checkout & laboratree.context.permissions.document) {
							c.items.get('checkout-document').show();
						}
					}
					else
					{
						if(laboratree.site.permissions.docs.checkin & laboratree.context.permissions.document) {
							c.items.get('checkin-document').show();
						}

						if(laboratree.site.permissions.docs.cancel_checkout & laboratree.context.permissions.document) {
							c.items.get('cancel-checkout-document').show();
						}
					}
				}
				else
				{
					if(laboratree.site.permissions.docs.add & laboratree.context.permissions.document) {
						c.items.get('add-document').show();
						c.items.get('add-folder').show();
					}

					if(node.draggable)
					{
						if(laboratree.site.permissions.docs.edit & laboratree.context.permissions.document) {
							c.items.get('edit-folder').show();
						}

						if(laboratree.site.permissions.docs['delete'] & laboratree.context.permissions.document) {
							c.items.get('delete-node').show();
						}
					}
				}

				c.contextNode = node;
				c.showAt(e.getXY());
			},
			startdrag: function(tree, node, e) {
				this.oldPosition = node.parentNode.indexOf(node);
				this.oldNextSibling = node.nextSibling;
                        },
			movenode: function(tree, node, oldParent, newParent, position) {
				var url = reparent_url;

				var params = {
					node: node.id,
					parent: newParent.id,
					position: position
				};

				if(oldParent == newParent) {
					url = reorder_url;

					params = {
						node: node.id,
						delta: (position-this.oldPosition)
					};
				}

				tree.disable();

				Ext.Ajax.request({
					url: url,
					params: params,
					success: function(response, request) {
						var data = Ext.decode(response.responseText);
						if(!data) {
							request.failure(response, request);
							return;
						}

						if(!data.success) {
							request.failiure(response, request);
							return;
						}

						tree.enable();
					},
					failure: function() {
						tree.suspendEvents();
						oldParent.appendChild(node);
						if(this.oldNextSibling) {
							oldParent.insertBefore(node, this.oldNextSibling);
						}

						tree.resumeEvents();
						tree.enable();
					}
				});
			},
			click: function(node, checked) {
				var details = Ext.getCmp('details-panel');
				if(!details) {
					return;
				}

				if(node) {
					/* Set Panel ID in Node Attributes for Template */
					node.attributes.panel_id = node.getOwnerTree().id;

					if(node.leaf) {
						if(node.attributes.status == 'Checked In') {
							laboratree.docs.dashboard.templates.file.checkedin.overwrite(details.body, node.attributes);
						} else {
							laboratree.docs.dashboard.templates.file.checkedout.overwrite(details.body, node.attributes);
						}
					} else {
						if(node.draggable) {
							laboratree.docs.dashboard.templates.folder.overwrite(details.body, node.attributes);
						} else {
							laboratree.docs.dashboard.templates.root.overwrite(details.body, node.attributes);
						}
					}
				} else {
					details.body.update(laboratree.docs.dashboard.details);
				}
			}
		}
	});

	laboratree.docs.dashboard.add(this.treepanel);
};

laboratree.docs.Doc = function(table_type, table_id, panel_id, parent_id, data_url) {
	Ext.QuickTips.init();

	this.table_type = table_type;
	this.table_id = table_id;
	this.panel_id = panel_id;
	this.parent_id = parent_id;
	this.data_url = data_url;

	this.stores = {};

	this.windows = [];

	this.templates = {
		doctype: new Ext.Template([
			'<div class="middle">',
				'<div><a href="#" onclick="laboratree.docs.doc.addType(\'{data_url}\'); return false;" title="Add Doc Type">Add Doc Type</div>',
			'</div>'
		])
	};

	this.typelistStore = new Ext.data.JsonStore({
		root: 'types',
		autoLoad: true,
		url: data_url,
		baseParams: {
			action: 'types'
		},
		fields: [
			'id', 'name'
		],
		listeners: {
			load: function(store, records, options) {
				var EmptyRecord = Ext.data.Record.create(['id', 'name']);
				var emptyRecord = new EmptyRecord({
					id: '',
					name: 'No Type'
				});

				store.insert(0, emptyRecord);
			}
		}
	});

	this.types = new Ext.form.ComboBox({
		xtype: 'combo',
		name: 'data[Type][name]',

		store: this.typelistStore,

		emptyText: 'No Type Selected...',

		fieldLabel: 'Type',
		valueField: 'id',
		displayField: 'name',
		editable: false,
		allowBlank: true,

		forceSelection: true,
		triggerAction: 'all',
		selectOnFocus: true,

		listeners: {
			select: function(combo, value) {
				if(value.data.id)
				{
					laboratree.docs.doc.addDocType(value.data.id);
				}
			}
		}
	});

	this.typeStore = new Ext.data.ArrayStore({
		fields: [
			'id',
			'title',
			'data'
		]
	});

	this.typeGrid = new Ext.grid.GridPanel({
		title: 'Document Types',

		store: this.typeStore,

		stripeRows: true,

		height: 150,

		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: false
			},
			columns: [{
				id: 'title',
				header: 'Title',
				dataIndex: 'title',
				width: '90%'
			},{
				id: 'actions',
				header: 'Actions',
				dataIndex: 'id',
				width: 70,
				align: 'center',
				renderer: laboratree.docs.render.add.Type
			}]
		})
	});

	this.form = new Ext.FormPanel({
		labelAlign: 'top',
		autoHeight: true,
		buttonAlign: 'center',
		frame: false,
		fileUpload: true,
		forceLayout: false,
		border: false,
		layout: 'form',

		items: [{
			id: 'DocumentTitle',
			xtype: 'textfield',
			fieldLabel: 'Title',
			name: 'data[Doc][title]',
			allowBlank: false,
			maxLength: 255,
			border: false,
			anchor: '100%',
			vtype: 'docTitle'
		},{
			id: 'DocumentFile',
			xtype: 'fileuploadfield',
			fieldLabel: 'Document',
			name: 'data[Doc][filename]',
			emptyText: 'Select a Document...',
			allowBlank: false,
			maxLength: 255,
			border: false,
			anchor: '100%'
		},{
			xtype: 'hidden',
			name: 'data[Doc][parent_id]',
			value: parent_id,
			border: false
		},{
			id: 'DocumentDescription',
			xtype: 'textarea',
			fieldLabel: 'Description',
			name: 'data[Doc][description]',
			height: 100,
			border: false,
			anchor: '100%'
		},{
			id: 'DocumentTag',
			xtype: 'textfield',
			fieldLabel: 'Tags',
			border: false,
			name: 'data[Doc][tags]',
			emptyText: 'Seperate Tags with Commas...',
			anchor: '100%'
		},{
			id: 'types',
			layout: 'form',
			border: false,
			items: [this.types,{
				id: 'type-data',
				xtype: 'hidden',
				name: 'data[Type][data]'
			},this.typeGrid]
		}],
		buttons: [{
			text: 'Add Document',
			handler: function () {
				laboratree.docs.doc.typeStore.each(function(record) {
					laboratree.docs.doc.gridToForm(record.data, 'data[DocTypeData]', laboratree.docs.doc.form);
				});

				laboratree.docs.doc.form.doLayout(false, true);

				if(laboratree.docs.doc.form.getForm().isValid()) {
					laboratree.docs.doc.form.getForm().submit({
						url: laboratree.docs.doc.data_url,
						waitMsg: 'Uploading document...',
						success: function(formpanel, o) {
							var win = formpanel.findParentByType('window');

							var treepanel = Ext.getCmp(panel_id);
							if(!treepanel)
							{
								if(win) {
									win.close();
								}
								return;
							}

							var treeloader = treepanel.getLoader();
							var parentnode = treepanel.getNodeById(parent_id);
							if(parentnode)
							{
								treeloader.load(parentnode, function() {
									parentnode.expand();
								});
							}

							if(win) {
								win.close();
							}
						}
					});
				}
			}
		},{
			text: 'Cancel',
			handler: function(btn, e) {
				var win = btn.findParentByType('window');
				if(win) {
					win.close();
				}
			}
		}]
	});

	var win = new Ext.Window({
		title: 'Add Document',
		autoHeight: true,
		buttonAlign: 'center',
		width: 500,

		items: [this.form]
	});

	win.show(this);
};

laboratree.docs.Doc.prototype.gridToForm = function(type, name, container)
{
	var type_id = type.data.type_id;
	var row_id = type.id;
	var field_id = null;
	var subtype_id = null;

	name += '[types][' + type_id + '][rows][' + row_id + ']';

	for(field_id in type.data.fields) {
		if(type.data.fields.hasOwnProperty(field_id)) {
			var value = type.data.fields[field_id];

			var hidden = new Ext.form.Hidden({
				name: name + '[fields][' + field_id + ']',
				value: value
			});

			if(container) {
				container.add(hidden);
			}
		}
	}

	for(subtype_id in type.data.subtypes) {
		if(type.data.subtypes.hasOwnProperty(subtype_id)) {
			var subtype = type.data.subtypes[subtype_id];

			this.gridToForm(subtype, name, container);
		}
	}
};

laboratree.docs.Doc.prototype.addDocType = function(type_id) {
	Ext.Ajax.request({
		url: String.format(laboratree.links.docs.fields, type_id) + '.json',
		success: function(response, request) {
			var data = Ext.decode(response.responseText);
			if(!data) {
				request.faiure(response, request);
				return;
			}

			if(!data.type) {
				request.failure(response, request);
				return;
			}

			var node_id = Ext.id();

			this.typeTree = new Ext.tree.TreePanel({
				flex: 1,

				useArrows: true,
				autoScroll: true,
				animate: true,
				containerScroll: true,
				frame: true,

				root: new Ext.tree.TreeNode({
					id: node_id,
					text: data.type.name,
					leaf: true,

					type: data.type.name,
					type_id: data.type.id,
					listeners: {
						click: function(node, e) {
							laboratree.docs.doc.fieldArea.layout.setActiveItem('type-panel-' + node.id);
						}
					}
				})
			});

			var fieldPanel = new Ext.Panel({
				id: 'type-panel-' + node_id,
				node: node_id,
				cls: 'field-panel',
				layout: 'form',
				buttonAlign: 'center'
			});

			Ext.each(data.fields, function(item, index, allItems) {
				this.addDocTypeField(item, fieldPanel, node_id);
			}, this);

			Ext.each(data.subtypes, function(item, index, allItems) {
				this.addDocSubtype(item, fieldPanel, node_id);
			}, this);

			this.fieldArea = new Ext.Panel({
				flex: 2,
				cls: 'field-area',
				layout: 'card',
				frame: true,
				activeItem: 0,

				items: [fieldPanel]
			});

			this.typeForm = new Ext.form.FormPanel({
				items: [{
					layout: 'hbox',
					layoutConfig: {
						align: 'stretch',
						pack: 'start'
					},

					anchor: '100% 100%',

					items: [this.typeTree, this.fieldArea]
				}]
			});


			this.typeWindow = new Ext.Window({
				title: 'Add Document Type: ' + data.type.name,
				layout: 'fit',

				width: 700,
				height: 500,

				closable: true,

				items: [this.typeForm],

				buttonAlign: 'center',

				buttons: [{
					text: 'Save ' + data.type.name,
					handler: function(btn, e) {
						var win = btn.findParentByType('window');
						if(win) {
							win.close();
						}
					}
				},{
					text: 'Cancel',

					handler: function(btn, e) {
						var win = btn.findParentByType('window');
						if(win) {
							win.close();
						}
					}
				}]
			});

			this.typeWindow.show();
		},
		failure: function(response, request) {

		},
		scope: this
	});
};

laboratree.docs.Doc.prototype.addDocTypeField = function(field, container, node_id, value) {
	var required = Boolean(field.required);
	var fld = new Ext.form.TextField({
		fieldLabel: field.name,
		name: 'data[DocsTypeField][' + node_id + '][' + field.id + ']',
		allowBlank: !required,
		display: field.display
	});

	// Text and Number are identical to defauilt field
	switch(field.type) {
		case 'boolean':
			fld = new Ext.form.Checkbox({
				fieldLabel: field.name,
				name: 'data[DocsTypeField][' + node_id + '][' + field.id + ']',
				inputValue: 1,
				value: 0,
				display: field.display
			});
			break;
	}

	fld.addListener('blur', function(field) {
		var node = laboratree.docs.doc.typeTree.getNodeById(field.ownerCt.node);
		if(!node) {
			return;
		}

		var value = field.getValue();
		if(field.display && value != '') {
			node.setText(node.attributes.type + ': ' + value);
		}
	});

	if(value) {
		fld.setValue(value);
	}

	container.add(fld);
};

laboratree.docs.Doc.prototype.addDocSubtype = function(subtype, container, node) {
	container.addButton({
		text: 'Add ' + subtype.name,
		node: node
	}, function(btn, e) {
		Ext.Ajax.request({
			url: String.format(laboratree.links.docs.fields, subtype.id) + '.json',
			success: function(response, request) {
				var data = Ext.decode(response.responseText);
				if(!data) {
					request.faiure(response, request);
					return;
				}

				if(!data.type) {
					request.failure(response, request);
					return;
				}

				// Add To TreePanel
				var parentNode = laboratree.docs.doc.typeTree.getNodeById(btn.node);
				var node = parentNode.appendChild({
					text: data.type.name,

					type: data.type.name,
					type_id: data.type.id,
					leaf: true,

					listeners: {
						click: function(node, e) {
							laboratree.docs.doc.fieldArea.layout.setActiveItem('type-panel-' + node.id);
						}
					}
				});
				parentNode.expand();
				node.select();

				var fieldPanel = new Ext.Panel({
					id: 'type-panel-' + node.id,
					node: node.id,
					layout: 'form',
					buttonAlign: 'center'
				});

				Ext.each(data.fields, function(item, index, allItems) {
					laboratree.docs.doc.addDocTypeField(item, fieldPanel, node.id);
				}, this);

				Ext.each(data.subtypes, function(item, index, allItems) {
					laboratree.docs.doc.addDocSubtype(item, fieldPanel, node.id);
				}, this);

				// Add Field Panel to Field Layout
				laboratree.docs.doc.fieldArea.add(fieldPanel);
				laboratree.docs.doc.fieldArea.layout.setActiveItem('type-panel-' + node.id);

				// Change ParentNode to Non-Leaf
			},
			failure: function(response, request) {

			},
			scope: this
		});
	});
};

laboratree.docs.Doc.prototype.addForm = function(type_id) {
	Ext.Ajax.request({
		url: String.format(laboratree.links.docs.fields, type_id) + '.json',
		success: function(response, request) {
			var data = Ext.decode(response.responseText);

			var form = new Ext.form.FormPanel({
				id: 'form-' + data.type.id,
				frame: true,

				items: []
			});

			Ext.each(data.fields, function(item, index, allItems) {
				this.addField(item, form);
			}, this);

			var stores = {};

			Ext.each(data.subtypes, function(item, index, allItems) {
				stores[item.id] = new Ext.data.ArrayStore({
					fields: [
						'title',
						'data'
					]
				});

				this.addSubtype(item, form, stores[item.id]);
			}, this);

			var title = '';
			if(this.windows.length > 0) {
				title = this.windows[this.windows.length - 1].title + ' > ';
			}

			title += data.type.name;

			var win = new laboratree.docs.TypeWindow({
				title: title,
				layout: 'fit',

				width: 500,
				height: 400,

				closable: true,

				items: [form],

				type_id: type_id,
				type_name: data.type.name,
				fields: data.fields,
				subtypes: data.subtypes,
				stores: stores,

				buttonAlign: 'center',

				buttons: [{
					text: 'Save ' + data.type.name,
					handler: function() {
						/* Get Window Object */
						var last = laboratree.docs.doc.windows.pop();

						/* Get Type ID for Window */
						var type_id = last.type_id;

						/* Get Fields from Window */
						var form = last.items.items[0];
						var fields = form.getForm().getFieldValues();

						/* Get Display Field */
						var display_id = 0;
						Ext.each(last.fields, function(item, index, allItems) {
							if(item.display) {
								display_id = item.id;
							}
						}, this);

						var title = fields['DocsTypeField-' + display_id];
						if(!title) {
							title = last.type_name;
						}

						/* Get Grid Data from Window */
						var store_id = null;
						var subtypes = [];

						var subtypeData = function(record) {
							subtypes[record.id] = record.data;
							//subtypes.push(record.data);
						};

						for(store_id in last.stores) {
							if(last.stores.hasOwnProperty(store_id)) {
								var lastStore = last.stores[store_id];
								lastStore.each(subtypeData, this);
							}
						}

						/* Destroy Window */
						last.destroy();

						var newRowId = Ext.id();
						var tRecord = new laboratree.docs.TypeRecord({
							id: newRowId,
							title: title,
							data: {
								type_id: type_id,
								fields: fields,
								subtypes: subtypes
							}
						}, newRowId);

						if(laboratree.docs.doc.windows.length > 0) {
							/* Get Current Window */
							var current = laboratree.docs.doc.windows[laboratree.docs.doc.windows.length - 1];

							/* Get Subtype Store */
							var store = current.stores[type_id];

							if(store) {
								store.add(tRecord);
							}

							current.show();
						} else {
							laboratree.docs.doc.typeStore.add(tRecord);
							laboratree.docs.doc.types.reset();
						}
					}
				}]
			});

			if(this.windows.length > 0) {
				win.addButton({
					xtype: 'button',
					text: 'Back to ' + this.windows[this.windows.length - 1].type_name,

					handler: function() {
						var last = laboratree.docs.doc.windows.pop();
						last.destroy();

						var current = laboratree.docs.doc.windows[laboratree.docs.doc.windows.length - 1];
						current.show();
					}
				});
			} else {
				win.addButton({
					xtype: 'button',
					text: 'Cancel',

					handler: function() {
						var last = laboratree.docs.doc.windows.pop();
						last.destroy();

						laboratree.docs.doc.types.reset();
					}
				});
			}

			Ext.each(this.windows, function(item, index, allItems) {
				item.hide();
			});

			this.windows.push(win);

			win.show();

		},
		scope: this
	});
};

laboratree.docs.Doc.prototype.addSubtype = function(subtype, container, store) {
	var grid = new Ext.grid.GridPanel({
		title: subtype.name,

		store: store,

		stripeRows: true,

		height: 150,

		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: false
			},
			columns: [{
				id: 'title',
				header: 'Title',
				dataIndex: 'title',
				width: '90%'
			},{
				id: 'actions',
				header: 'Actions',
				dataIndex: 'id',
				width: 70,
				align: 'center',
				renderer: laboratree.docs.render.add.subType
			}]
		}),

		bbar: [{
			xtype: 'button',
			text: 'Add ' + subtype.name,

			handler: function() {
				laboratree.docs.doc.addForm(subtype.id);
			}
		}]
	});

	container.add(grid);
};

laboratree.docs.Doc.prototype.addField = function(field, container, value) {
	var required = Boolean(field.required);
	var fld = new Ext.form.TextField({
		fieldLabel: field.name,
		name: 'DocsTypeField-' + field.id,
		allowBlank: !required
	});

	// Text and Number are identical to default
	switch(field.type) {
		case 'boolean':
			fld = new Ext.form.Checkbox({
				fieldLabel: field.name,
				name: 'DocsTypeField-' + field.id,
				inputValue: 1,
				value: 0
			});
			break;
		default:
	}

	if(value) {
		fld.setValue(value);
	}

	container.add(fld);
};

laboratree.docs.Doc.prototype.editForm = function(row_id, type_id) {
	var store = null;
	if(laboratree.docs.doc.windows.length > 0) {
		var wnd = laboratree.docs.doc.windows[laboratree.docs.doc.windows.length - 1];
		store = wnd.stores[type_id];
	} else {
		store = laboratree.docs.doc.typeStore;
	}

	if(!store) {
		return;
	}

	var row = store.getById(row_id);
	if(!row) {
		return;
	}

	var row_data = row.data.data;

	type_id = row_data.type_id;

	Ext.Ajax.request({
		url: String.format(laboratree.links.docs.fields, type_id) + '.json',
		success: function(response, request) {
			var data = Ext.decode(response.responseText);

			var form = new Ext.form.FormPanel({
				id: 'form-' + data.type.id,
				frame: true,

				items: []
			});

			Ext.each(data.fields, function(item, index, allItems) {
				var value = row_data.fields['DocsTypeField-' + item.id];
				this.addField(item, form, value);
			}, this);

			var stores = {};

			Ext.each(data.subtypes, function(item, index, allItems) {
				stores[item.id] = new Ext.data.ArrayStore({
					fields: [
						'title',
						'data'
					]
				});

				this.addSubtype(item, form, stores[item.id]);
			}, this);

			var subtype_row_id = null;
			for(subtype_row_id in row_data.subtypes) {
				if(row_data.subtypes.hasOwnProperty(subtype_row_id)) {
					var subtype = row_data.subtypes[subtype_row_id];

					var store = stores[subtype.data.type_id];

					if(store) {
						var tRecord = new laboratree.docs.TypeRecord({
							id: subtype.id,
							title: subtype.title,
							data: subtype.data
						}, subtype.id);

						stores[subtype.data.type_id].add(tRecord);
					}
				}
			}

			var title = '';
			if(this.windows.length > 0) {
				title = this.windows[this.windows.length - 1].title + ' > ';
			}

			title += data.type.name;

			var win = new laboratree.docs.TypeWindow({
				title: title,
				layout: 'fit',

				width: 500,
				height: 400,

				closable: true,

				items: [form],

				row_id: row_id,
				type_id: type_id,
				type_name: data.type.name,
				fields: data.fields,
				subtypes: data.subtypes,
				stores: stores,

				buttonAlign: 'center',

				buttons: [{
					text: 'Save ' + data.type.name,
					handler: function() {
						/* Get Window Object */
						var last = laboratree.docs.doc.windows.pop();

						/* Get Row ID for Window */
						var row_id = last.row_id;

						/* Get Type ID for Window */
						var type_id = last.type_id;

						/* Get Fields from Window */
						var form = last.items.items[0];
						var fields = form.getForm().getFieldValues();

						/* Get Display Field */
						var display_id = 0;
						Ext.each(last.fields, function(item, index, allItems) {
							if(item.display) {
								display_id = item.id;
							}
						}, this);

						var title = fields['DocsTypeField-' + display_id];
						if(!title) {
							title = last.title;
						}

						/* Get Grid Data from Window */
						var subtypes = [];
						var store_id = null;
						var recordPush = function(record) {
							subtypes.push(record.data);
						};
						for(store_id in last.stores) {
							if(last.stores.hasOwnProperty(store_id)) {
								var lastStore = last.stores[store_id];
								lastStore.each(recordPush, this);
							}
						}

						/* Destroy Window */
						last.destroy();

						var newRowId = Ext.id();
						var tRecord = new laboratree.docs.TypeRecord({
							id: newRowId,
							title: title,
							data: {
								type_id: type_id,
								fields: fields,
								subtypes: subtypes
							}
						}, newRowId);

						var oldRecord = null;
						if(laboratree.docs.doc.windows.length > 0) {
							/* Get Current Window */
							var current = laboratree.docs.doc.windows[laboratree.docs.doc.windows.length - 1];

							/* Get Subtype Store */
							var store = current.stores[type_id];
							if(store) {
								oldRecord = store.getById(row_id);
								store.remove(oldRecord);
								store.add(tRecord);
							}

							current.show();
						} else {
							oldRecord = laboratree.docs.doc.typeStore.getById(row_id);
							laboratree.docs.doc.typeStore.remove(oldRecord);

							laboratree.docs.doc.typeStore.add(tRecord);
							laboratree.docs.doc.types.reset();
						}
					}
				}]
			});

			if(this.windows.length > 0) {
				win.addButton({
					xtype: 'button',
					text: 'Back to ' + this.windows[this.windows.length - 1].type_name,

					handler: function() {
						var last = laboratree.docs.doc.windows.pop();
						last.destroy();

						var current = laboratree.docs.doc.windows[laboratree.docs.doc.windows.length - 1];
						current.show();
					}
				});
			} else {
				win.addButton({
					xtype: 'button',
					text: 'Cancel',

					handler: function() {
						var last = laboratree.docs.doc.windows.pop();
						last.destroy();

						laboratree.docs.doc.types.reset();
					}
				});
			}

			Ext.each(this.windows, function(item, index, allItems) {
				item.hide();
			});

			this.windows.push(win);

			win.show();
		},
		scope: this
	});
};

laboratree.docs.Doc.prototype.deleteType = function(row_id, type_id) {
	Ext.MessageBox.confirm('Delete Document Type', 'Are you sure?', function(btn) {
		if(btn == 'yes') {
			var store;
			if(laboratree.docs.doc.windows.length > 0) {
				var wnd = laboratree.docs.doc.windows[laboratree.docs.doc.windows.length - 1];
				store = wnd.stores[type_id];
			} else {
				store = laboratree.docs.doc.typeStore;
			}
		
			if(!store) {
				return;
			}
		
			var row = store.getById(row_id);
			if(!row) {
				return;
			}
		
			store.remove(row);
		}
	});
};

laboratree.docs.Doc.prototype.addType = function(types, container) {
	container.removeAll();

	var card_id = 0;

	Ext.each(types, function(item, index, allItems) {
		var type = item.Type;
		var fields = item.DocsTypeField;

		var form = new Ext.Panel({
			id: 'type-' + card_id,
			title: type.name,
			layout: 'form',

			autoHeight: true,
			frame: true
		});

		Ext.each(fields, function(item, index, allItems) {
			var required = Boolean(item.required);
			var field = new Ext.form.TextField({
				fieldLabel: item.name,
				name: 'data[DocsTypeField][' + type.id + '][' + item.id + ']',
				allowBlank: !required
			});
	
			switch(item.type) {
				case 'boolean':
					field = new Ext.form.Checkbox({
						fieldLabel: item.name,
						name: 'data[DocsTypeField][' + type.id + '][' + item.id + ']',
						inputValue: 1,
						value: 0
					});
					break;
				default:
			}

			form.add(field);
		}, this);

		if(fields.length > 0)
		{
			container.add(form);
			card_id++;
		}
	}, this);
};

laboratree.docs.Doc.prototype.navigate = function(direction) {
	var layout = laboratree.docs.doc.cards.getLayout();
	var length = laboratree.docs.doc.cards.items.items.length;

	var idx = parseInt(layout.activeItem.id.split('type-')[1], 10);

	var increment = (direction == 'prev') ? -1 : 1;
	var next = idx + increment;

	if(next < length)
	{
		layout.setActiveItem(next);
	}
	else
	{
		// TODO: Figure out if we use values, current unused
		var values = laboratree.docs.doc.winform.getValues(true);
		this.win.close();
		return;
	}

	Ext.getCmp('move-prev').setDisabled(next == 0);

	Ext.getCmp('move-next').setText('Next');
	if((next + 1) >= length)
	{
		Ext.getCmp('move-next').setText('Done');
	}
};

laboratree.docs.Doc.prototype.initType = function() {
	this.types = new Ext.TabPanel({
		id: 'DocsTypes',
		minTabWidth: 110,
		maxTabWidth: 150,
		enableTabScroll: true,
		border: false,
		activeTab: 0,
		deferredRender: false,
		
		defaults: {
			layout: 'form',
			frame: true,
			autoScroll: true,
			closable: true,
			forceLayout: true,
			anchor: '100% 100%'
		},
		
		items: [{
			id: 'addtype',
			title: 'Add Type',
			closable: false
		}],
		
		listeners: {
			beforetabchange: function(panel, newTab, currentTab) {
				if(currentTab) {
					if(panel.items.items.length < 2) {
						return false;
					}
					else if(newTab.id == 'addtype') {
						laboratree.docs.doc.addType();
						return false;
					}
				}
			}
		}
	});
	
	var tabPanel = Ext.getCmp('types');
	tabPanel.removeAll();
	tabPanel.add(this.types);
	tabPanel.doLayout(false, true);	
};

laboratree.docs.deleteType = function(button_id) {
	Ext.MessageBox.confirm('Delete Document/Folder', 'Are you sure?', function(btn) {
		if(btn == 'yes') {
			laboratree.docs.doc.form.remove(button_id);	
		}
	});
};

laboratree.docs.makeEdit = function(doc_id, panel_id, parent_id) {
	var data_url = String.format(laboratree.links.docs.edit, doc_id) + '.extjs';

	if(!parent_id) {
		var panel = Ext.getCmp(panel_id);
		if(!panel) {
			return false;
		}

		var node = panel.getNodeById(doc_id);
		if(!node) {
			return false;
		}

		parent_id = node.parentNode.attributes.id;
	}

	laboratree.docs.edit = new laboratree.docs.Edit(doc_id, panel_id, parent_id, data_url);

	Ext.Ajax.request({
		url: data_url,
		params: {
			action: 'edit'
		},
		success: function(response, request) {
			var data = Ext.decode(response.responseText);
			if(!data) {
				request.failure(response, request);
				return;
			}

			if(!data.success) {
				request.failure(response, request);
				return;
			}

			if(!data.data) {
				request.failure(response, request);
				return;
			}

			if(!data.types) {
				request.failure(response, request);
				return;
			}

			var field;
			for(field in data.data) {
				if(data.data.hasOwnProperty(field)) {
					var value = data.data[field];

					var cmp = Ext.getCmp(field);
					if(cmp) {
						if(cmp.setValue) {
							cmp.setValue(value);
						}
					}
				}
			}

			var row_id;
			for(row_id in data.types) {
				if(data.types.hasOwnProperty(row_id)) {
					var type = data.types[row_id];
					var tRecord = new laboratree.docs.TypeRecord(type, row_id);
					laboratree.docs.edit.typeStore.add(tRecord);
				}
			}
		},
		failure: function(response, request) {

		}
	}, this);
};

laboratree.docs.Edit = function(doc_id, panel_id, parent_id, data_url) {
	Ext.QuickTips.init();

	this.doc_id = doc_id;
	this.panel_id = panel_id;
	this.parent_id = parent_id;
	this.data_url = data_url;
	
	this.stores = {};

	this.windows = [];

	this.typelistStore = new Ext.data.JsonStore({
		root: 'types',
		autoLoad: true,
		url: data_url,
		baseParams: {
			action: 'types'
		},
		fields: [
			'id', 'name'
		],
		listeners: {
			load: function(store, records, options) {
				var EmptyRecord = Ext.data.Record.create(['id', 'name']);		
				var emptyRecord = new EmptyRecord({
					id: '',
					name: 'No Type'
				});

				store.insert(0, emptyRecord);
			}
		}
	});
	
	this.types = new Ext.form.ComboBox({
		xtype: 'combo',	
		name: 'data[Type][name]',
		
		store: this.typelistStore,

		emptyText: 'No Type Selected...',

		fieldLabel: 'Type',
		valueField: 'id',
		displayField: 'name',
		editable: false,
		allowBlank: true,

		forceSelection: true,
		triggerAction: 'all',
		selectOnFocus: true,
		
		listeners: {
			select: function(combo, value) {
				if(value.data.id)
				{
					laboratree.docs.edit.addForm(value.data.id);
				}
			}
		}
	});

	this.typeStore = new Ext.data.ArrayStore({
		fields: [
			'id',
			'title',
			'data'
		]
	});

	this.typeGrid = new Ext.grid.GridPanel({
		title: 'Document Types',

		store: this.typeStore,

		stripeRows: true,

		height: 150,

		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: false
			},
			columns: [{
				id: 'title',
				header: 'Title',
				dataIndex: 'title',
				width: '90%'
			},{
				id: 'actions',
				header: 'Actions',
				dataIndex: 'id',
				width: 70,
				align: 'center',
				renderer: laboratree.docs.render.edit.Type
			}]
		})
	});

	this.form = new Ext.FormPanel({
		labelAlign: 'top',
		autoHeight: true,
		buttonAlign: 'center',
		frame: true,
		forceLayout: true,
		layout: 'form',

		defaults: {
			forceLayout: true
		},
		
		items: [{
			id: 'title',
			xtype: 'textfield',
			fieldLabel: 'Title',
			name: 'data[Doc][title]',
			allowBlank: false,
			anchor: '100%'
		},{
			id: 'description',
			xtype: 'textarea',
			fieldLabel: 'Description',
			name: 'data[Doc][description]',
			height: 100,
			anchor: '100%'
		},{
			id: 'tags',
			xtype: 'textfield',
			fieldLabel: 'Tags',
			name: 'data[Doc][tags]',
			emptyText: 'Seperate Tags with Commas...',
			anchor: '100%'
		},{
			id: 'types',
			layout: 'form',
			items: [this.types,{
				id: 'type-data',
				xtype: 'hidden',
				name: 'data[Type][data]'
			},this.typeGrid]
		}],
		buttons: [{
			text: 'Save Document',
			handler: function () {
				laboratree.docs.edit.typeStore.each(function(record) {
					laboratree.docs.edit.gridToForm(record.data, 'data[DocTypeData]', laboratree.docs.edit.form);
				});

				laboratree.docs.edit.form.doLayout(false, true);

				if(laboratree.docs.edit.form.getForm().isValid()) {
					laboratree.docs.edit.form.getForm().submit({
						url: laboratree.docs.edit.data_url,
						waitMsg: 'Saving document...',
						success: function(formpanel, o) {
							var win = formpanel.findParentByType('window');

							var treepanel = Ext.getCmp(panel_id);
							if(!treepanel)
							{
								win.close();
								return;
							}
						
							var treeloader = treepanel.getLoader();
							var parentnode = treepanel.getNodeById(parent_id);
							if(parentnode)
							{
								treeloader.load(parentnode, function() {
									parentnode.expand();
								});
							}
							win.close();
						}
					});
				}
			}
		},{
			text: 'Cancel',
			handler: function(btn) {
				var win = btn.findParentByType('window');
				win.close();
			}
		}]
	});

	var win = new Ext.Window({
		title: 'Edit Document',
		autoHeight: true,
		buttonAlign: 'center',
		width: 500,

		items: [this.form]
	});

	win.show(this);
};

laboratree.docs.Edit.prototype.gridToForm = function(type, name, container) {
	var type_id = type.data.type_id;
	var row_id = type.id;

	name += '[types][' + type_id + '][rows][' + row_id + ']';

	var field_id;
	for(field_id in type.data.fields) {
		if(type.data.fields.hasOwnProperty(field_id)) {
			var value = type.data.fields[field_id];

			var hidden = new Ext.form.Hidden({
				name: name + '[fields][' + field_id + ']',
				value: value
			});

			if(container) {
				container.add(hidden);
			}
		}
	}

	var subtype_id;
	for(subtype_id in type.data.subtypes) {
		if(type.data.subtypes.hasOwnProperty(subtype_id)) {
			var subtype = type.data.subtypes[subtype_id];
			this.gridToForm(subtype, name, container);
		}
	}
};

laboratree.docs.Edit.prototype.addForm = function(type_id) {
	Ext.Ajax.request({
		url: String.format(laboratree.links.docs.fields, type_id) + '.json',
		success: function(response, request) {
			var data = Ext.decode(response.responseText);

			var form = new Ext.form.FormPanel({
				id: 'form-' + data.type.id,
				frame: true,

				items: []
			});

			Ext.each(data.fields, function(item, index, allItems) {
				this.addField(item, form);
			}, this);

			var stores = {};

			Ext.each(data.subtypes, function(item, index, allItems) {
				stores[item.id] = new Ext.data.ArrayStore({
					fields: [
						'title',
						'data'
					]
				});

				this.addSubtype(item, form, stores[item.id]);
			}, this);

			var title = '';
			if(this.windows.length > 0) {
				title = this.windows[this.windows.length - 1].title + ' > ';
			}

			title += data.type.name;

			var win = new laboratree.docs.TypeWindow({
				title: title,
				layout: 'fit',

				width: 500,
				height: 400,

				closable: true,

				items: [form],

				type_id: type_id,
				type_name: data.type.name,
				fields: data.fields,
				subtypes: data.subtypes,
				stores: stores,

				buttonAlign: 'center',

				buttons: [{
					text: 'Save ' + data.type.name,
					handler: function() {
						/* Get Window Object */
						var last = laboratree.docs.edit.windows.pop();

						/* Get Type ID for Window */
						var type_id = last.type_id;

						/* Get Fields from Window */
						var form = last.items.items[0];
						var fields = form.getForm().getFieldValues();

						/* Get Display Field */
						var display_id = 0;
						Ext.each(last.fields, function(item, index, allItems) {
							if(item.display) {
								display_id = item.id;
							}
						}, this);

						var title = fields['DocsTypeField-' + display_id];
						if(!title) {
							title = last.title;
						}

						/* Get Grid Data from Window */
						var subtypes = [];
						var store_id;

						var addRecord = function(record) {
							subtypes[record.id] = record.data;
						};

						for(store_id in last.stores) {
							if(last.stores.hasOwnProperty(store_id)) {
								var store = last.stores[store_id];

								store.each(addRecord, this);
							}
						}

						/* Destroy Window */
						last.destroy();

						var newRowId = Ext.id();
						var tRecord = new laboratree.docs.TypeRecord({
							id: newRowId,
							title: title,
							data: {
								type_id: type_id,
								fields: fields,
								subtypes: subtypes
							}
						}, newRowId);

						if(laboratree.docs.edit.windows.length > 0) {
							/* Get Current Window */
							var current = laboratree.docs.edit.windows[laboratree.docs.edit.windows.length - 1];

							/* Get Subtype Store */
							var subtypeStore = current.stores[type_id];

							if(subtypeStore) {
								subtypeStore.add(tRecord);
							}
	
							current.show();
						} else {
							laboratree.docs.edit.typeStore.add(tRecord);
							laboratree.docs.edit.types.reset();
						}
					}
				}]
			});

			if(this.windows.length > 0) {
				win.addButton({
					xtype: 'button',
					text: 'Back to ' + this.windows[this.windows.length - 1].type_name,

					handler: function() {
						var last = laboratree.docs.edit.windows.pop();
						last.destroy();

						var current = laboratree.docs.edit.windows[laboratree.docs.edit.windows.length - 1];
						current.show();
					}
				});
			} else {
				win.addButton({
					xtype: 'button',
					text: 'Cancel',

					handler: function() {
						var last = laboratree.docs.edit.windows.pop();
						last.destroy();

						laboratree.docs.edit.types.reset();
					}
				});
			}

			Ext.each(this.windows, function(item, index, allItems) {
				item.hide();
			});

			this.windows.push(win);

			win.show();

		},
		scope: this
	});
};

laboratree.docs.Edit.prototype.addSubtype = function(subtype, container, store) {
	var grid = new Ext.grid.GridPanel({
		title: subtype.name,

		store: store,

		stripeRows: true,

		height: 150,

		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: false
			},
			columns: [{
				id: 'title',
				header: 'Title',
				dataIndex: 'title',
				width: '90%'
			},{
				id: 'actions',
				header: 'Actions',
				dataIndex: 'id',
				width: 70,
				align: 'center',
				renderer: laboratree.docs.render.edit.subType
			}]
		}),

		bbar: [{
			xtype: 'button',
			text: 'Add ' + subtype.name,

			handler: function() {
				laboratree.docs.edit.addForm(subtype.id);
			}
		}]
	});

	container.add(grid);
};

laboratree.docs.Edit.prototype.addField = function(field, container, value) {
	var required = Boolean(field.required);
	var fld = new Ext.form.TextField({
		fieldLabel: field.name,
		name: 'DocsTypeField-' + field.id,
		allowBlank: !required
	});

	switch(field.type) {
		case 'boolean':
			fld = new Ext.form.Checkbox({
				fieldLabel: field.name,
				name: 'DocsTypeField-' + field.id,
				inputValue: 1,
				value: 0
			});
			break;
	}

	if(value) {
		fld.setValue(value);
	}

	container.add(fld);
};

laboratree.docs.Edit.prototype.editForm = function(row_id, type_id) {
	var store;
	if(laboratree.docs.edit.windows.length > 0) {
		var wnd = laboratree.docs.edit.windows[laboratree.docs.edit.windows.length - 1];
		store = wnd.stores[type_id];
	} else {
		store = laboratree.docs.edit.typeStore;
	}

	if(!store) {
		return;
	}

	var row = store.getById(row_id);
	if(!row) {
		return;
	}

	var row_data = row.data.data;

	Ext.Ajax.request({
		url: String.format(laboratree.links.docs.fields, row_data.type_id) + '.json',
		success: function(response, request) {
			var data = Ext.decode(response.responseText);

			var form = new Ext.form.FormPanel({
				id: 'form-' + data.type.id,
				frame: true,

				items: []
			});

			Ext.each(data.fields, function(item, index, allItems) {
				var value = row_data.fields['DocsTypeField-' + item.id];
				this.addField(item, form, value);
			}, this);

			var stores = {};

			Ext.each(data.subtypes, function(item, index, allItems) {
				stores[item.id] = new Ext.data.ArrayStore({
					fields: [
						'title',
						'data'
					]
				});

				this.addSubtype(item, form, stores[item.id]);
			}, this);

			var subtype_row_id;
			for(subtype_row_id in row_data.subtypes) {
				if(row_data.subtypes.hasOwnProperty(subtype_row_id)) {
					var subtype = row_data.subtypes[subtype_row_id];
					var store = stores[subtype.data.type_id];

					if(store) {
						var tRecord = new laboratree.docs.TypeRecord({
							id: subtype.id,
							title: subtype.title,
							data: subtype.data
						}, subtype.id);

						stores[subtype.data.type_id].add(tRecord);
					}
				}
			}

			var title = '';
			if(this.windows.length > 0) {
				title = this.windows[this.windows.length - 1].title + ' > ';
			}

			title += data.type.name;

			var win = new laboratree.docs.TypeWindow({
				title: title,
				layout: 'fit',

				width: 500,
				height: 400,

				closable: true,

				items: [form],

				row_id: row_id,
				type_id: type_id,
				type_name: data.type.name,
				fields: data.fields,
				subtypes: data.subtypes,
				stores: stores,

				buttonAlign: 'center',

				buttons: [{
					text: 'Save ' + data.type.name,
					handler: function() {
						/* Get Window Object */
						var last = laboratree.docs.edit.windows.pop();

						/* Get Row ID for Window */
						var row_id = last.row_id;

						/* Get Type ID for Window */
						var type_id = last.type_id;

						/* Get Fields from Window */
						var form = last.items.items[0];
						var fields = form.getForm().getFieldValues();

						/* Get Display Field */
						var display_id = 0;
						Ext.each(last.fields, function(item, index, allItems) {
							if(item.display) {
								display_id = item.id;
							}
						}, this);

						var title = fields['DocsTypeField-' + display_id];
						if(!title) {
							title = last.title;
						}

						/* Get Grid Data from Window */
						var subtypes = [];
						var store_id;

						var addRecord = function(record) {
							subtypes.push(record.data);
						};

						for(store_id in last.stores) {
							if(last.stores.hasOwnProperty(store_id)) {
								var store = last.stores[store_id];
								store.each(addRecord, this);
							}
						}

						/* Destroy Window */
						last.destroy();

						var newRowId = Ext.id();
						var tRecord = new laboratree.docs.TypeRecord({
							id: newRowId,
							title: title,
							data: {
								type_id: type_id,
								fields: fields,
								subtypes: subtypes
							}
						}, newRowId);

						var oldRecord;
						if(laboratree.docs.edit.windows.length > 0) {
							/* Get Current Window */
							var current = laboratree.docs.edit.windows[laboratree.docs.edit.windows.length - 1];

							/* Get Subtype Store */
							var subtypeStore = current.stores[type_id];

							if(subtypeStore) {
								oldRecord = subtypeStore.getById(row_id);
								subtypeStore.remove(oldRecord);

								subtypeStore.add(tRecord);
							}
	
							current.show();
						} else {
							oldRecord = laboratree.docs.edit.typeStore.getById(row_id);
							laboratree.docs.edit.typeStore.remove(oldRecord);

							laboratree.docs.edit.typeStore.add(tRecord);
							laboratree.docs.edit.types.reset();
						}
					}
				}]
			});

			if(this.windows.length > 0) {
				win.addButton({
					xtype: 'button',
					text: 'Back to ' + this.windows[this.windows.length - 1].type_name,

					handler: function() {
						var last = laboratree.docs.edit.windows.pop();
						last.destroy();

						var current = laboratree.docs.edit.windows[laboratree.docs.edit.windows.length - 1];
						current.show();
					}
				});
			} else {
				win.addButton({
					xtype: 'button',
					text: 'Cancel',

					handler: function() {
						var last = laboratree.docs.edit.windows.pop();
						last.destroy();

						laboratree.docs.edit.types.reset();
					}
				});
			}

			Ext.each(this.windows, function(item, index, allItems) {
				item.hide();
			});

			this.windows.push(win);

			win.show();
		},
		scope: this
	});
};

laboratree.docs.Edit.prototype.deleteType = function(row_id, type_id) {
	Ext.MessageBox.confirm('Delete Document Type', 'Are you sure?', function(btn) {
		if(btn == 'yes') {
			var store;
			if(laboratree.docs.edit.windows.length > 0) {
				var wnd = laboratree.docs.edit.windows[laboratree.docs.edit.windows.length - 1];
				store = wnd.stores[type_id];
			} else {
				store = laboratree.docs.edit.typeStore;
			}
		
			if(!store) {
				return;
			}
		
			var row = store.getById(row_id);
			if(!row) {
				return;
			}
		
			store.remove(row);
		}
	});
};

laboratree.docs.makeCheckIn = function(doc_id, panel_id, parent_id) {
	var data_url = String.format(laboratree.links.docs.checkin, doc_id) + '.extjs';

	if(!parent_id) {
		var panel = Ext.getCmp(panel_id);
		if(!panel) {
			return false;
		}

		var node = panel.getNodeById(doc_id);
		if(!node) {
			return false;
		}

		parent_id = node.parentNode.attributes.id;
	}

	laboratree.docs.checkin = new laboratree.docs.CheckIn(doc_id, panel_id, parent_id, data_url);
};

laboratree.docs.CheckIn = function(doc_id, panel_id, parent_id, data_url)
{
	Ext.QuickTips.init();

	this.doc_id = doc_id;
	this.panel_id = panel_id;
	this.parent_id = parent_id;
	this.data_url = data_url;
	
	this.stores = {};
	
	this.form = new Ext.FormPanel({
		labelAlign: 'top',
		autoHeight: true,
		buttonAlign: 'center',
		frame: true,
		fileUpload: true,
		forceLayout: true,

		defaults: {
			forceLayout: true
		},
		
		items: [{
			xtype: 'fileuploadfield',
			fieldLabel: 'Document',
			name: 'data[Doc][filename]',
			allowBlank: false,
			emptyText: 'Select a document...',
			anchor: '100%'
		},{
			id: 'changelog',
			xtype: 'textarea',
			fieldLabel: 'Changes',
			name: 'data[DocsVersion][changelog]',
			height: 100,
			anchor: '100%'
		}],

		buttons: [{
			text: 'Check In Document',
			handler: function () {
				if(laboratree.docs.checkin.form.getForm().isValid()) {
					laboratree.docs.checkin.form.getForm().submit({
						url: laboratree.docs.checkin.data_url,
						waitMsg: 'Saving document...',
						success: function(formpanel, o) {
							var treepanel = Ext.getCmp(laboratree.docs.checkin.panel_id);
							if(!treepanel)
							{
								laboratree.docs.checkin.win.close();
								return;
							}
						
							var treeloader = treepanel.getLoader();
							var parentnode = treepanel.getNodeById(laboratree.docs.checkin.parent_id);
							if(parentnode)
							{
								treeloader.load(parentnode, function() {
									parentnode.expand();
								});
							}
							laboratree.docs.checkin.win.close();
						}
					});
				}
			}
		},{
			text: 'Cancel',
			handler: function() {
				laboratree.docs.checkin.win.close();
			}
		}]
	});

	this.win = new Ext.Window({
		title: 'Check In Document',
		autoHeight: true,
		buttonAlign: 'center',
		width: 500,

		items: [this.form]
	});

	this.win.show(this);
};

laboratree.docs.makeShared = function(div, data_url, table_type, table_id) {
	laboratree.docs.shared = new laboratree.docs.Shared(div, data_url, table_type, table_id);
};

laboratree.docs.Shared = function(div, data_url, table_type, table_id) {
	Ext.QuickTips.init();

	Ext.state.Manager.setProvider(new Ext.state.CookieProvider({
		expires: new Date(new Date().getTime() + (1000 * 60 * 60 * 24 * 7))
	}));

	this.div = div;
	this.data_url = data_url;
	this.table_type = table_type;
	this.table_id = table_id;

	var loader = new Ext.tree.TreeLoader({
		url: data_url,
		uiProviders: {
			'col': Ext.tree.TreeNodeUI
		},

		table_type: table_type,
		table_id: table_id,

		listeners: {
			beforeload: function(store, options) {
				laboratree.docs.masks.shared = new Ext.LoadMask('documents-shared-' + store.table_type + '-' + store.table_id, {
					msg: 'Loading...'
				});
				laboratree.docs.masks.shared.show();
			},
			load: function(store, records, options) {
				laboratree.docs.masks.shared.hide();
			}
		}
	});

	var tree = new Ext.tree.TreePanel({
		id: 'documents-shared-' + table_type + '-' + table_id,
		height: 500,
		renderTo: div,

		table_type: table_type,
		table_id: table_id,

		rootVisible: false,
		autoScroll: true,
		animate: false,
		containerScroll: true,
		lines: true,

		stateEvents: ['collapsenode', 'expandnode'],
		stateful: true,
		getState: function() {
			var nodes = [];
			this.getRootNode().eachChild(function(child) {
				var storeTreeState = function(node, expandNodes) {
					if(node.isExpanded() && node.childNodes.length > 0) {
						expandNodes.push(node.getPath());
						node.eachChild(function(child) {
							storeTreeState(child, expandNodes);
						});
					}
				};
				storeTreeState(child, nodes);
			});

			return {
				expandedNodes: nodes
			};
		},
		applyState: function(state) {
			var that = this;
			this.getLoader().on('load', function() {
				var cookie = Ext.state.Manager.get('documents-shared-' + that.table_type + '-' + that.table_id);
				if(cookie) {
					var nodes = cookie.expandedNodes;
					var i = 0;
					for(i = 0; i < nodes.length; i++) {
						if(typeof nodes[i] != 'undefined') {
							that.expandPath(nodes[i]);
						}
					}
				}
			});
		},

		loader: loader,

		root: new Ext.tree.AsyncTreeNode({
			text: 'Root',
			expanded: true
		})
	});
};

laboratree.docs.render = {};

laboratree.docs.render.versions = {};

laboratree.docs.render.versions.version = function(value, p, record) {
	return String.format('<a href="' + laboratree.links.docs.download + '" title="Download Version {2}">{2}</a>', record.data.doc_id, record.data.id, value);
};

laboratree.docs.render.versions.size = function(value, p, record) {
	return value;
};

laboratree.docs.render.versions.author = function(value, p, record) {
	return String.format('<a href="' + laboratree.links.users.profile + '" title="{1}">{1}</a>', value, record.data.author);
};

laboratree.docs.render.versions.changelog = function(value, p, record) {
	return String.format('<img src="/img/icons/unread_mail.gif" alt="Changes" ext:qtip="{0}" style="cursor: pointer;" />', value);
};

laboratree.docs.render.versions.signature = function(value, p, record) {
	return String.format('<a href="' + laboratree.links.docs.checksum + '" title="SHA-1 Signature">SHA-1</a>', record.data.doc_id, value);
};

laboratree.docs.render.add = {};

laboratree.docs.render.add.Type = function(value, p, record) {
	var actions = '';
	actions += '<a href="#" onclick="laboratree.docs.doc.editForm(\'{0}\', \'{1}\'); return false;" title="Edit Type">Edit</a>';
	actions += ' | ';
	actions += '<a href="#" onclick="laboratree.docs.doc.deleteType(\'{0}\', \'{1}\'); return false;" title="Remove Type">Delete</a>';

	return String.format(actions, value, record.data.data.type_id);
};

laboratree.docs.render.add.subType = function(value, p, record) {
	var actions = '';
	actions += '<a href="#" onclick="laboratree.docs.doc.editForm(\'{0}\', \'{1}\'); return false;" title="Edit Subtype">Edit</a>';
	actions += ' | ';
	actions += '<a href="#" onclick="laboratree.docs.doc.deleteType(\'{0}\', \'{1}\'); return false;" title="Remove Subtype">Delete</a>';

	return String.format(actions, value, record.data.data.type_id);
};

laboratree.docs.render.edit = {};

laboratree.docs.render.edit.Type = function(value, p, record) {
	var actions = '';
	actions += '<a href="#" onclick="laboratree.docs.edit.editForm(\'{0}\', \'{1}\'); return false;" title="Edit Type">Edit</a>';
	actions += ' | ';
	actions += '<a href="#" onclick="laboratree.docs.edit.deleteType(\'{0}\', \'{1}\'); return false;" title="Remove Type">Delete</a>';

	return String.format(actions, value, record.data.data.type_id);
};

laboratree.docs.render.edit.subType = function(value, p, record) {
	var actions = '';
	actions += '<a href="#" onclick="laboratree.docs.edit.editForm(\'{0}\', \'{1}\'); return false;" title="Edit Subtype">Edit</a>';
	actions += ' | ';
	actions += '<a href="#" onclick="laboratree.docs.edit.deleteType(\'{0}\', \'{1}\'); return false;" title="Remove Subtype">Delete</a>';

	return String.format(actions, value, record.data.data.type_id);
};
