<?php

if ( !function_exists('doc_comment_clean') ){
	function doc_comment_clean( $string ){
		return str_replace(array("\n","\t","\r"), ' ', str_replace(array('*','/'), '', $string));
	}	
}

$classes = array('WP_Object', 'WP_Global_Object', 'WP_DB_Object', 'WP_DB_Object_With_Taxonomies', 'WP_Post_Object', 'WP_User_Object', 'WP_Taxonomy_Object', 'WP_Term_Object');

?>
<div class="wrap">

	<div id="icon-options-general" class="icon32"><br /></div>
	
	<h2><?php _e('WP Objects'); ?></h2>
	
	<div class="clear"></div>
	
	<table id="all-plugins-table" class="widefat">
			
		<thead>
			<tr>
				<th class="manage-column" scope="col">Class</th>
				<th class="manage-column" scope="col">Properties</th>
				<th class="manage-column" scope="col">Description</th>
			</tr>
		</thead>
		
		<tbody class="plugins">
					
			<?php foreach($classes as $class ) : 
				
				$reflect = new ReflectionClass($class); ?>
			
				<tr class="<?php echo $class; ?> active">
					
					<td class="plugin-title">
					
						<strong><?php echo $class; ?></strong>
						
						<?php 
							if ( $parent = $reflect->getParentClass() ){
							
								echo '<em>Extends: <b>' . $parent->getName() . '</b></em>';
								
								$parent_props = $parent->getProperties();
								$parent_methods = $parent->getMethods();
							}
						?>
						
					</td><!-- .plugin-title -->
					<td>
						<div class="row-actions-visible">
							<p>
							<?php 
								$vars = $reflect->getProperties();
								
								foreach($vars as $var){
									if ( isset($parent_props) && in_array($var, $parent_props) )
										continue;
									echo $var . '<br>';
								} 
							?>
							</p>
						</div>
					</td>
					<td class="column-description desc">
						<div class="plugin-description">
							<?php 
							
								$methods = $reflect->getMethods();
								
								foreach($methods as $method){
										
									if ( isset($parent_methods) && in_array($method, $parent_methods) )
										continue;
									
									echo '<p>';
									
									$param_str = '';
									
									foreach($method->getParameters() as $param){
										
										if ( $param->isPassedByReference() )
											$param_str .= '&';	
										
										$param_str .= ' $' . $param->getName();
										
										if ( $param->isDefaultValueAvailable() ) {
											
											$default = $param->getDefaultValue();
											
											if ( is_string($default) )		$default = '"' . $default . '"';
											elseif ( true === $default )	$default = 'true';
											elseif ( false === $default )	$default = 'false';
											elseif ( null === $default )	$default = 'null';
											
											$param_str .= ' = ' . $default . '';
										}
										
										$param_str .= ', ';
									}
									
									$params = trim($param_str, ', '); 	
									
									echo '<b>';
									
									if ( $method->isStatic() ){
										echo '<small style="color:#E60">STATIC</small> ';	
									}
									
									if ( $method->isFinal() ){
										echo '<small style="color:#930">FINAL</small> ';
									}
									if ( $method->isAbstract() ){
										echo '<small style="color:#163">ABSTRACT</small> ';	
									}
									
									if ( $method->isPublic() ){
										echo '<em style="color:#036">Public</em> ';	
									} elseif ( $method->isPrivate() ){
										echo '<em style="color:#836">Private</em> ';	
									} elseif ($method->isProtected()){
										echo '<em style="color:#613">Protected</em> ';	
									}
									
									echo '</b>';
									
									echo '<code style="color:#222; background:#f0f0f0"><strong>' . $method->getName() . '</strong>(' . ( !empty($params) ? " $params " : '' ) . ');</code> ';
									
									echo doc_comment_clean( $method->getDocComment() );
									
									echo '</p>';
								}	
		
							?>
						</div>
					</td>
				
				</tr>
			
			<?php endforeach; ?>
		</tbody>
	</table>
</div>