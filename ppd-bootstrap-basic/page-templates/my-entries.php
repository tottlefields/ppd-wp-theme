<?php /* Template Name: My Entries */ ?>
<?php 


global $current_user, $wpdb;
get_currentuserinfo();

if(!is_user_logged_in()) {
	wp_redirect(site_url('/login/'));
	exit;
}

$userId = $current_user->ID;

?>
<?php get_header(); ?>

<div id="content" class="standard">
    <div class="container">
        <div class="row">
            <div class="col-md-12" id="main-content">

               	<?php
				
				if(isset($_GET['view']) && $_GET['view']>0) {
					$show = get_post( $_GET['view'] );
					$title = $show->post_title;
					?>					
	            	<h1 class="title">ENTRY: <?php echo $title; ?> <span class="pull-right"><a class="btn btn-info" href="/account/my-entries/">My Entries</a>
	            	&nbsp;<a href="/enter-show/individual-classes/?edit=yes&show=<?php echo $_GET['view']; ?>" class="btn btn-primary">Edit Entry</a></span></h1>
					<?php 

					$dogData = get_dogs_for_user($userId);
					
					$args = array (
						'post_type'	=> 'entries',
						'post_status'	=> array('publish'),
						'order'		=> 'ASC',
						'numberposts'	=> -1,
						'author'		=> $userId,
						'meta_query' 	=> array(
							array(
								'key'		=> 'show_id-pm',
								'compare'	=> '=',
								'value'		=> $_GET['view'],
							),
						)
					);
					
					// get posts
					$posts = get_posts($args);
					global $post;
					
					echo '<table style="margin-bottom:0px;"  class="table table-striped table-hover table-responsive">';
					foreach( $posts as $post ) {	
						setup_postdata( $post );
						$entry_data = get_field('entry_data-pm', false, false);
						foreach ($entry_data as $dog_id => $dogEntry){
							$dog = get_dog_by_id($dogData, $dog_id);
							$height = ($dogEntry == 'nfc') ? 'NFC' : $dogEntry['height'];
							echo '<tr><th colspan="3"><h3><span style="color:'.$dog['dog_color'].'">'.$dog['pet_name'].'</span><span class="pull-right"><small>'.$height.'</small></span></h3></th></tr>';
							if($dogEntry == 'nfc'){ continue; }
							foreach ($dogEntry['classes'] as $classNo => $classDetails){
								echo '<tr>
									<td>'.DateTime::createFromFormat('Y-m-j', $classDetails['date'])->format('l').'</td>
									<td>'.$classNo.'. '.$classDetails['class_title'].'</td>
									<td>'.$classDetails['handler'].'</td>
								</tr>';
							}
						}
					}
					echo '</table>';
				}
				else{
					?>
	            	<h1 class="title">My Entries <span class="pull-right"><a href="/account/" class="btn btn-info">My Account</a>
	            	&nbsp;<a class="btn btn-primary" href="/enter-show/">Enter Show</a></span></h1>
					<?php 
					$args = array (
							'post_type'		=> 'entries',
							'post_status'	=> array('publish'),
							'numberposts'	=> -1,
							'author'		=> $userId
					);
					
					// get posts
					$posts = get_posts($args);
					global $post;
					$shows = array();
			
					// loop
					if( $posts ) {
						$total_money = 0;
						$outstanding = 0;
						foreach( $posts as $post ) {	
							setup_postdata( $post );
							$show_id = get_field('show_id-pm', false, false);
							$total_cost = get_field('total_cost-pm');
							$total_money += $total_cost;
							$outstanding += $total_cost;
							$paid_details = get_field('paid-pm');
							if (isset($paid_details) && $paid_details != ''){
								foreach ($paid_details as $payment){
									$outstanding -= $payment['amount'];
								}
							}
							array_push($shows, $show_id);	
						}
						wp_reset_postdata();
						
						$show_args = array(
							'post_type'		=> 'shows',
							'post_status'	=> array('publish'),
							'meta_key'	=> 'start_date',
							'orderby'	=> 'meta_value',
							'order'		=> 'ASC',
							'numberposts'	=> -1,
							'include'		=> $shows
						);
						// get posts
						$show_posts = get_posts($show_args);
						if( $show_posts ) {	
							?>						
							<table class="table table-bordered table-striped table-rounded">
								<tr>
									<th class="text-center">Date(s)</th>
									<th class="text-center">Show</th>
									<th class="text-center">Closes</th>
									<th class="text-center">Total</th>
									<th class="text-center">Outstanding</th>
									<th class="text-center"></th>
								</tr>
							<?php 
							
							foreach( $show_posts as $post ) {	
								setup_postdata( $post );
	
								$start_date = new DateTime(get_field('start_date', false, false));
								$end_date 	= new DateTime(get_field('end_date', false, false));
								$close_date = new DateTime(get_field('close_date', false, false));
								$show_dates = $start_date->format('jS M');
								if($start_date != $end_date){
									$show_dates .= ' to '.$end_date->format('jS M Y');
								}
								else{
									$show_dates .= $start_date->format(' Y');
								}
								?>
								<tr>
									<td class="text-center"><?php echo $show_dates; ?></td>
									<td class="text-center"><?php echo get_the_title(); ?></td>
									<td class="text-center"><?php echo $close_date->format('jS M'); ?></td>
									<td class="text-center">&pound;<?php echo sprintf("%.2f", $total_money); ?></td>
									<td class="text-center">
									<?php if ($outstanding > 0){ 
										$paypal_money = $outstanding+($outstanding*0.035)+0.3;
										?>
										&pound;<?php echo sprintf("%.2f", $outstanding); ?>
										<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	                                        <input type="hidden" name="cmd" value="_xclick">
                                       	 	<input type="hidden" name="business" value="agilityaid@outlook.com">
	                                        <input type="hidden" name="amount" value="<?php echo $paypal_money ?>">
                                        	<input type="hidden" name="item_name" value="<?php echo get_the_title(); ?> Entry Fees">
	                                        <INPUT TYPE="hidden" NAME="currency_code" value="GBP">
	                                        <INPUT TYPE="hidden" NAME="return" value="<?php echo get_site_url(); ?>/process-paypal/?result=done&entry=<?php echo the_ID(); ?>&amount=<?php echo $total_money; ?>&user=<?php echo $userId; ?>">
	                                        <input type="hidden" name="first_name" value="<?php echo $current_user->user_firstname; ?>">
	                                        <input type="hidden" name="last_name" value="<?php echo $current_user->user_firstname; ?>">
	                                        <input type="hidden" name="email" value="<?php echo $current_user->user_email; ?>">
	                                        <input type="image" name="submit" border="0"
	                                        src="https://www.paypalobjects.com/en_US/i/btn/btn_buynow_LG.gif"
	                                        alt="PayPal - The safer, easier way to pay online">
	                                	</form>
	                                <?php }
	                                else{ ?>
	                                <span class="label label-success">PAID</span>
	                                <?php } ?>
                                	</td>
									<td class="text-center" width="195px">
										<a class="btn btn-default btn-sm" href="/account/my-entries/?view=<?php echo the_ID(); ?>">View Entry</a>
										<a class="btn btn-default btn-sm" href="/enter-show/individual-classes/?show=<?php echo the_ID(); ?>">Edit Entry</a>
									</td>
								</tr>
								<?php
							}
							echo '</table>
							<div class="alert alert-warning">When paying by PayPal, please note that there is a handling fee of 3.5% + 30p added to your transaction.</div>';
							wp_reset_postdata();
						}
					
					} else {
						// no entries found
						echo '
						<div class="alert">You currently have no shows entered on our system. To enter a show, please go to the <a href="/enter-show">Enter Show</a> page.</div>';
					}
				}
				?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>