if (!OCA.EOSBrowser) 
{
	OCA.EOSBrowser = {};
}

OCA.EOSBrowser.App = 
{
	allInstancesList: null,

	initializeAll: function($el) 
	{
		if (this.allInstancesList)
		{
			return this.allInstancesList;
		}
		
		this.allInstancesList = new OCA.EOSBrowser.FileList(
			$el,
			{
				id: 'eosbrowser.self',
				scrollContainer: $('#app-content'),
				fileActions: this._createFileActions(),
				personalPage: false
			}
		);

		this.allInstancesList.appName = t('files_eosbrowser', 'EOS Browser');
		this.allInstancesList.$el.find('#emptycontent').html('<div class="icon-settings"></div>' +
			'<h2>' + t('files_eosbrowser', 'No contents in this folder') + '</h2>' +
			'<p>' + t('files_eosbrowser', 'Files from EOS instances that you are allowed to see will appear here') + '</p>');
		return this.allInstancesList;
	},
	
	removeAllContent: function()
	{
		if (this.allInstancesList)
		{
			this.allInstancesList.$fileList.empty();
		}
	},
	
	/**
	 * Destroy the app
	 */
	destroy: function() 
	{
		this.removeAllContent();
		this.removePersonalContent();
		this.allInstancesList = null;
		this.personalList = null;
	},

	_createFileActions: function() 
	{
		
		var fileActions = new OCA.Files.FileActions();
		
		// We let the default navigation handler to fetch the content (this allow us to use the default
		// ajax calls implementation by letting ownCloud to resolve the instance as a "share")
		fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) 
		{
			if(context.$file.attr('custom_perm') === '1') {
				OCA.Files.App.setActiveView('files', {silent: true});
				OCA.Files.App.fileList.changeDirectory(context.$file.attr('data-path') + '/' + filename, true, true);
			} else {
				OC.dialogs.alert('You do not have permission to browse ' + filename, 'Error');
			}
		});
		fileActions.setDefault('dir', 'Open');
		return fileActions;
	},
};

$(document).ready(function() 
{
	OCA.Files.TagsPlugin.allowedLists.push('eosbrowser.self');
	$('#app-content-eosbrowser').on('show', function(e)
	{
		OCA.EOSBrowser.App.initializeAll($(e.target));
	});
	$('#app-content-eosbrowser').on('hide', function() 
	{
		OCA.EOSBrowser.App.removeAllContent();
	});
	$('#app-content-eosbrowser-personal').on('show', function(e)
	{
		OCA.EOSBrowser.App.initializePersonal($(e.target));
	});
	$('#app-content-eosbrowser-personal').on('hide', function()
	{
		OCA.EOSBrowser.App.removePersonalContent();
	});
});

