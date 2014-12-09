/**
 * Initialization script for maps on page load
 **/

//Global Objects
var userid;

$(function() { //shorthand for: $(document).ready(function() {
   //Call setup functions
   setupUser();
   queryBarBehavior();
   searchBehavior();
   advanceSearch();
   geocodingBox();
   menuDivPanels();
   progressBar();
   sortableLayers();
   setupAlertAccordion();
   //initializeBrowserFocus();
   queryStatementBehavior();

   //Setup functions definitions =========================================================
   function setupUser() {
      //Main userid global setting
      userid = 'icodeuser';   //temporarily set to icodeuser for testing

      $(document).ready(function() {
         $('#username').text(userid);
      });
   }

   function queryBarBehavior() {
      /*
      //Query bar text control
      //Select all text if query bar comes into focus
      $('#query:text').focus(function() { 
         $(this).one('mouseup', function(event){
            //event.preventDefault();
         }).select();
      });
      */
   }

   function searchBehavior() {
      $('#searchBarForm').submit(function(e) {
         e.preventDefault();
         console.debug('Regular search initiated.');
         advancedSearchEnabled = false;
         search();
      });

      $('#advancedSearchForm').submit(function(e) {
         e.preventDefault();
         console.debug('Advanced search initiated.');
         advancedSearchEnabled = true;
         search();
      });

      //When search button clicked (or hit 'return' key)
      $('#searchBtn').click( function() {
         advancedSearchEnabled = false;
         search();
      });
   }

   function advanceSearch() {
      //Handle toggling advanced search box
      $('#advancedSearchToggle').mousedown( function() {
         $('#advancedSearchDropdown').toggle();

         if ( !$('#advancedSearchDropdown').is(":visible") ) {
            //Show the box
            $('#advancedSearchToggle').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
         }
         else {
            //Hide the box
            $('#advancedSearchToggle').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
         }
      });

      //Hide advanced search if clicked outside of box
      $(document).mouseup( function(e) {
         var container = $("#advancedSearchDropdown");

         if ( $('#advancedSearchDropdown').is(":visible")
            && !container.is(e.target) // if the target of the click isn't the container...
            && container.has(e.target).length === 0 // ... nor a descendant of the container
            && !$('#advancedSearchToggle').is(e.target) )
         {
            $('#advancedSearchToggle').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
            container.hide();
         }
      });
   }

   function geocodingBox() {
      $("#geocode-form").submit(function(e) {
         e.preventDefault();
         codeAddress();
      });

      //Geocoding text box control
      $('#geocodeAddress')
         .focus(function() { 
            $(this).one('mouseup', function(event){
               //event.preventDefault();
            }).select();

            //Release focus on input field if clicked outside
            /*
            $(document).mouseup(function (e) {
               if (!$('#geocodeAddress').is(e.target)) {
                  $('#geocodeAddress').blur();
               }
            });
            */
         });
   }

   function menuDivPanels() {
      //Initialize the menuDiv panels' collapsing behavior
      $('.panel-collapse').collapse({'toggle': false});

      //Control the behavior of menu heading clicking
      $('.menuHeading').on('click', function(e) {
         var panelToToggle = $(this).parents('.panel').children('.menuBody');
         var glyphiconToToggle = $(this).children('.btn').children('.glyphicon-menu');

         //Flip the icon
         if (panelToToggle.hasClass('in')) {
            glyphiconToToggle.removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
         }
         else {
            glyphiconToToggle.removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
         }

      //Hide all others, then toggle the one that was clicked
      $('.menuBody').collapse('hide');
      panelToToggle.collapse('toggle');
      });
   }

   function progressBar() {
      //Data reload progress bar style behavior initialization
      NProgress.configure({ 
         ease: 'ease', 
         trickleRate: 0.02,
         trickleSpeed: 50,
         showSpinner: false,
         minimum: 0.3,
      });
   }

   function sortableLayers() {
      //Sortable
      var displayedLayersList = $('#displayedLayersList');
      var hiddenLayersList = $('#hiddenLayersList');



      displayedLayersList.sortable({
         // Only make the .layerHeading child elements support dragging.
         handle: '.layerHeading',
         cursor: 'move',
         connectWith: '.connectedSortable',
         placeholder: 'ui-state-highlight',
         cancel: '.ui-state-disabled',
         update: listUpdated
      });
      hiddenLayersList.sortable({
         // Only make the .layerHeading child elements support dragging.
         handle: '.layerHeading',
         cursor: 'move',
         connectWith: '.connectedSortable',
         placeholder: 'ui-state-highlight',
         cancel: '.ui-state-disabled',
         //update: //only need to call update once, called from displayedLayersList.  listUpdated performs both functions
      });

      //Control the behavior of sorting manipulation via buttons
      $('.hideShowLayerBtn').on('mousedown', function(e) {
         //Look for the clicked panel's li element
         var thisLiElement = $(this).closest('.layerHeading').parent('.panel');

         //Skip disabled elements
         if (thisLiElement.hasClass('ui-state-disabled')) {
            //Don't move disabled layers
            return;
         }

         //Determine element's previous position based on minus icon, then move it to the opposite group
         if ($(this).hasClass('glyphicon-minus-sign')) {
            $('#hiddenLayersList').prepend(thisLiElement);
         }
         else {
            $('#displayedLayersList').append(thisLiElement);
         }

         //Then call the normal sortable list update function
         listUpdated();
      });

      //Control the behavior of layer options button clicking
      $('.layersOptionsBtn').on('click', function(e) {
         var panelToToggle = $(this).closest('.layerHeading').parents('.panel').children('.layerBody');
         var glyphiconToToggle = $(this).children('.glyphicon-menu');

         //Flip the icon
         if (panelToToggle.hasClass('in')) {
            glyphiconToToggle.removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
         }
         else {
            glyphiconToToggle.removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
         }

         //Toggle the options panel
         panelToToggle.collapse('toggle');
      });
   }

   function setupAlertAccordion() {
      $("#alertAccordion").accordion({
         collapsible: true,
      heightStyle: "content",
      autoHeight: false,
      });
      $(window).resize(function(){
         if ($("#alertAccordion").accordion != undefined) {
            $("#alertAccordion").accordion("refresh");
         }
      });
   }

   /* -------------------------------------------------------------------------------- */
   /**
    * 
    **/
   function initializeBrowserFocus() {
      function onBlur() {
         document.body.className = 'blurred';
         console.log('Browser tab out of focus');
         browserFocus = false;
      };

      function onFocus(){
         document.body.className = 'focused';
         console.log('Browser tab in focus');
         browserFocus = true;
         //Refresh the maps on focus if an attempt to refresh was made while out of focus
         if (queuedRefresh) {
            queuedRefresh = false;  //reset flag
            refreshMaps(true);
            refreshLayers();
         }
      };

      if (/*@cc_on!@*/false) { // check for Internet Explorer
         document.onfocusin = onFocus;
         document.onfocusout = onBlur;
      }
      else {
         window.onfocus = onFocus;
         window.onblur = onBlur;
      }
   }

   /* -------------------------------------------------------------------------------- */
   /**
    * 
    **/
   function queryStatementBehavior() {
      $('.queryStatement').click(function() {
         $(this).select();
      });
   }
});

//Globally exposed functions
//Function to control what happens after list is updated
function listUpdated() {
   $('.panel', displayedLayersList).each(function(index, elem) {
      var $listItem = $(elem);
      var newIndex = $listItem.index();   //updated indices
   });
   $('.panel', hiddenLayersList).each(function(index, elem) {
      var $listItem = $(elem);
      var newIndex = $listItem.index();   //updated indices
   });

   //Traverse down the elements, then back up to find the identity of the new shown panel
   var newShownLayerID = $('#displayedLayersList').find('.glyphicon-plus-sign').closest('.layerHeading').parent('.panel').attr('id');
   //Traverse down the elements, then back up to find the identity of the new hidden panel
   var newHiddenLayerID = $('#hiddenLayersList').find('.glyphicon-minus-sign').closest('.layerHeading').parent('.panel').attr('id');

   //Update hideShow button icons
   $('#displayedLayersList').children('.panel').children('.layerHeading').find('.hideShowLayerBtn').removeClass('glyphicon-plus-sign').addClass('glyphicon-minus-sign');
   //Update hideShow button icons
   $('#hiddenLayersList').children('.panel').children('.layerHeading').find('.hideShowLayerBtn').removeClass('glyphicon-minus-sign').addClass('glyphicon-plus-sign');

   //Update panel color based on layer visibility
   $('#displayedLayersList').children('.panel-default').removeClass('panel-default').addClass('panel-success');
   $('#displayedLayersList').find('.btn-default').removeClass('btn-default').addClass('btn-success');
   $('#hiddenLayersList').children('.panel-success').removeClass('panel-success').addClass('panel-default');
   $('#hiddenLayersList').find('.btn-success').removeClass('btn-success').addClass('btn-default');

   //Refresh layers on the map
   refreshLayers(newShownLayerID, newHiddenLayerID);
}
