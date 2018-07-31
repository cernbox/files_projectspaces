if (!OCA.ProjectSpaces) 
{
	OCA.ProjectSpaces = {};
}

OCA.ProjectSpaces.App = 
{
	allProjectsList: null,
	personalList: null,
	
	initializeAll: function($el) 
	{
		if (this.allProjectsList) 
		{
			return this.allProjectsList;
		}
		
		this.allProjectsList = new OCA.ProjectSpaces.FileList(
			$el,
			{
				id: 'projectspaces.self',
				scrollContainer: $('#app-content'),
				fileActions: this._createFileActions(),
				personalPage: false
			}
		);

		this.allProjectsList.appName = t('files_projectspaces', 'Project spaces repository');
		this.allProjectsList.$el.find('#emptycontent').html('<div class="icon-settings"></div>' +
			'<h2>' + t('files_projectspaces', 'No contents in this folder') + '</h2>' +
			'<p>' + t('files_projectspaces', 'Files from project spaces (That you are allowed to see) will appear here') + '</p>');
		return this.allProjectsList;
	},
	
	initializePersonal: function($el)
	{
		if (this.personalList) 
		{
			return this.personalList;
		}
		
		this.personalList = new OCA.ProjectSpaces.FileList(
			$el,
			{
				id: 'projectspaces-personal.self',
				scrollContainer: $('#app-content'),
				fileActions: this._createFileActions(),
				personalPage: true
			}
		);

		this.personalList.appName = t('files_projectspaces', 'Project spaces repository');
		this.personalList.$el.find('#emptycontent').html('<div class="icon-settings"></div>' +
			'<h2>' + t('files_projectspaces', 'No contents in this folder') + '</h2>' +
			'<p>' + t('files_projectspaces', 'Files from project spaces (That you are allowed to see) will appear here') + '</p>');
		return this.personalList;
	},

	removeAllContent: function() 
	{
		if (this.allProjectsList) 
		{
			this.allProjectsList.$fileList.empty();
		}
	},
	
	removePersonalContent: function()
	{
		if(this.personalList)
		{
			this.personalList.$fileList.empty();
		}
	},

	/**
	 * Destroy the app
	 */
	destroy: function() 
	{
		this.removeAllContent();
		this.removePersonalContent();
		this.allProjectsList = null;
		this.personalList = null;
	},

	_createFileActions: function() 
	{
		
		var fileActions = new OCA.Files.FileActions();
		
		// We let the default navigation handler to fetch the content (this allow us to use the default
		// ajax calls implementation by letting ownCloud to resolve the project as a "share")
		fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) 
		{
			OCA.Files.App.setActiveView('files', {silent: true});
			OCA.Files.App.fileList.changeDirectory(context.$file.attr('data-path') + '/' + filename, true, true);
		});
		fileActions.setDefault('dir', 'Open');
		return fileActions;
	},
};

$(document).ready(function() 
{
	OCA.Files.TagsPlugin.allowedLists.push('projectspaces.self');
	OCA.Files.TagsPlugin.allowedLists.push('projectspaces-personal.self');
	$('#app-content-projectspaces').on('show', function(e) 
	{
		OCA.ProjectSpaces.App.initializeAll($(e.target));
	});
	$('#app-content-projectspaces').on('hide', function() 
	{
		OCA.ProjectSpaces.App.removeAllContent();
	});
	$('#app-content-projectspaces-personal').on('show', function(e)
	{
		OCA.ProjectSpaces.App.initializePersonal($(e.target));
	});
	$('#app-content-projectspaces-personal').on('hide', function()
	{
		OCA.ProjectSpaces.App.removePersonalContent();
	});
});

