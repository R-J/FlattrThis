<?php if (!defined('APPLICATION')) exit();

$PluginInfo['FlattrThis'] = array(
   'Name' => 'Flattr this',
   'Description' => 'Adds a static <a href="https://flattr.com/">Flattr</a> button to each comment and into the profiles of users who add their flattr username to their profile info <a href="https://flattr.com/submit/auto?user_id=r-j&url=http%3A%2F%2Fvanillaforums.org%2Faddon%2Fflattrthis-plugin" target="_blank"><img src="//api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0"></a>',
   'Version' => '0.1',
   'Author' => 'Robin',
   'RequiredApplications' => array('Vanilla' => '>=2.0.18'),
   'HasLocale' => TRUE,
   'MobileFriendly' => TRUE,
   'License' => 'GPLv2'
);

class FlattrThisPlugin extends Gdn_Plugin {
  // set additional field in user table
   public function Setup() {
   Gdn::Structure()->Table('User')
      ->Column('FlattrUser', 'varchar(32)', TRUE)
      ->Set(FALSE, FALSE);
   }
  
   // delete additional field in user table
   public function OnDisable() {
      $Structure = Gdn::Structure();
      if ($Structure->Table('User')->ColumnExists('FlattrUser')) {      
         // $Structure->Table('User')->DropColumn('FlattrUser');
      }
   }

   // extra field for flattr User name in edit profile
   public function ProfileController_EditMyAccountAfter_Handler($Sender) {
      $HtmlOut = '<li>'.$Sender->Form->Label(T('Your Flattr User Name'), 'FlattrUser').$Sender->Form->TextBox('FlattrUser', array('class' => 'InputBox SmallInput')).'</li>';
      echo $HtmlOut;
   }
   
   // show flattr button to others in profile
   public function ProfileController_AfterRenderAsset_Handler($Sender, $Args) {
      if ($Args['AssetName'] != 'Panel') {
         return;
      }
      $User = $Sender->User;
      $FlattrUser = urlencode(Gdn_Format::Text($User->FlattrUser));
      
      // exit if its the own profile, or  user has no flattr account
      if (($User->UserID == Gdn::Session()->User->UserID) || ($FlattrUser == '')) {
         return;
      }
      
      $FlattrThing = urlencode($Sender->Request->Domain().UserUrl($User));
      echo Anchor(
         Img(
            '//api.flattr.com/button/flattr-badge-large.png',
            array(
               'alt' => T('FlattrThisImageAlt', 'Flattr this'),
               'title' => T('FlattrThisImageTitle', 'Flattr this')
            )
         ),
         'https://flattr.com/submit/auto?user_id='.$FlattrUser.'&url='.$FlattrThing,
         '',
         array('target' => '_blank')
      );
   }
   
   // show flattr name in own profile
   public function UserInfoModule_OnBasicInfo_Handler($Sender) {
      $FlattrUser = $Sender->User->FlattrUser;
      // only show info if it exists and only for your own profile
      if (($Sender->User->UserID != Gdn::Session()->User->UserID) || ($FlattrUser == '')) {
         return;
      }
      $HtmlOut = Wrap(T('Flattr User'), 'dt', array('class' => 'FlattrUser')).Wrap(Gdn_Format::Text($FlattrUser), 'dd', array('class' => 'FlattrUser'));

      echo $HtmlOut;
   }
   
  
  // show flattr buttons in discussion
   public function DiscussionController_AfterCommentBody_Handler($Sender) {
      $UserID = $Sender->EventArguments['Object']->InsertUserID;
      // don't show flattr buttons for your own content
      if ($UserID == Gdn::Session()->User->UserID) {
         return;
      }
      
  // only show for people having flattr account set up
      $User = Gdn::SQL()->GetWhere('User', array('UserID' => $UserID))->FirstRow();
      $FlattrUser = Gdn_Format::Text($User->FlattrUser);
      if(empty($FlattrUser)) {
         return;
      }

      $Discussion = $Sender->Discussion;
      $Comment = $Sender->EventArguments['Comment'];
     
      if ($Comment == '') {
         $FlattrThing = urlencode(Url('/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name), TRUE));      
      } else {
         $FlattrThing = urlencode(Url("/discussion/comment/{$Comment->CommentID}#Comment_{$Comment->CommentID}", TRUE));
      }
      
      echo Anchor(
         Img(
            '//api.flattr.com/button/flattr-badge-large.png',
            array(
               'alt' => T('FlattrThisImageAlt', 'Flattr this'),
               'title' => T('FlattrThisImageTitle', 'Flattr this')
            )
         ),
         'https://flattr.com/submit/auto?user_id='.$FlattrUser.'&url='.$FlattrThing,
         '',
         array('target' => '_blank')
      );
   }
}
