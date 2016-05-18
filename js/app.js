if (!OCA.ProjectSpaces) 
{
	OCA.ProjectSpaces = {};
}

OCA.ProjectSpaces.App = 
{
	_inFileList: null,
	
	initialize: function($el) 
	{
		if (this._inFileList) 
		{
			return this._inFileList;
		}

		this._inFileList = new OCA.ProjectSpaces.FileList(
			$el,
			{
				id: 'projectspaces.self',
				scrollContainer: $('#app-content'),
				fileActions: this._createFileActions()
			}
		);

		this._inFileList.appName = t('files_projectspaces', 'Project spaces repository');
		this._inFileList.$el.find('#emptycontent').html('<div class="icon-settings"></div>' +
			'<h2>' + t('files_projectspaces', 'No contents in this folder') + '</h2>' +
			'<p>' + t('files_projectspaces', 'Files from project spaces (That you are allowed to see) will appear here') + '</p>');
		return this._inFileList;
	},

	removeContent: function() 
	{
		if (this._inFileList) 
		{
			this._inFileList.$fileList.empty();
		}
	},

	/**
	 * Destroy the app
	 */
	destroy: function() 
	{
		this.removeContent();
	},

	_createFileActions: function() 
	{
		
		var fileActions = new OCA.Files.FileActions();
		
		// We let the default navigation handler to fetch the content (this allow us to use the default
		// ajax calls implementation by letting ownCloud to resolve the project as a "share")
		fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) 
		{
			if(context.$file.attr('custom_perm') === '1')
			{
				OCA.Files.App.setActiveView('files', {silent: true});
				OCA.Files.App.fileList.changeDirectory(context.$file.attr('data-path') + '/' + filename, true, true);
			}
			else
			{
				OC.dialogs.alert('You do not have enough permissions to browser ' + filename, 'Error');
			}
		});
		fileActions.setDefault('dir', 'Open');
		return fileActions;
	},
};

$(document).ready(function() 
{
	$('#app-content-projectspaces').on('show', function(e) 
	{
		OCA.ProjectSpaces.App.initialize($(e.target));
	});
	$('#app-content-projectspaces').on('hide', function() 
	{
		OCA.ProjectSpaces.App.destroy();
	});
});

