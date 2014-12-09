<?php

/**
 * Khooj (PHP 5.3+)
 * To find/update/delete data in multidimensional array based on JSON string.
 * @version 0.1 dev
 * @author Junaid Atari <mj.atari@gmail.com>
 * @link https://github.com/blacksmoke26/Khooj Github Repository
 * @copyright (c) 2014, Junaid Atari
 */
class Khooj
{
	/**
	 * Convert path into root chunks
	 * @access private
	 * @param string $query Query path
	 * @param array $params (optional) key=>value parameters to replace path string
	 * @throws Exception if query was empty
	 * @return array
	 */	
	private static function _pathToElements ( $query, array $params=array() )
	{
		if ( !trim($query) || !count (($list =preg_split('#(?<!\\\)\/#',$query))))
		{
			throw new Exception('Empty query not allowed');
		}
		
		$elements = array();
		$t=count($params);

		foreach ( $list as $value )
		{
			$collect=array();
			
			if ( is_numeric($value) )
			{
				$elements[] = (int) $value;
				continue;
			}
			
			$val = $t
				? str_replace ( array_keys ($params), array_values ($params), $value )
				: $value;
			
			//base[index,nextnode][attribute=>value]
			if ( preg_match('/^([^\]]*?)\[(\d+), ?([^\]]+)\]\[([^\]]+)=\>([^\]]+)\]$/is', (string)$val, $collect) )
			{
				$elements = array_merge($elements, array(
					(object) array(
						'type'=>'idx',
						'key'=>self::_numToInt($collect[1]),
						'val'=>$collect[2],
					),
					self::_numToInt($collect[3]),
					(object) array(
						'type'=>'attr',
						'key'=>self::_numToInt($collect[4]),
						'val'=>$collect[5],
					)
				));
				
				continue;
			}
			
			//[attribute=>value]
			if ( preg_match('/^\[(.*?)=\>(.*?)\]$/is', (string)$val, $collect) )
			{
				$elements[] = (object) array(
					'type'=>'attr',
					'key'=>self::_numToInt($collect[1]),
					'val'=>self::_numToInt($collect[2]),
				);
				
				continue;
			}
			
			//name[attribute=>value]
			if ( preg_match('/^(.*?)\[(.*?)=\>(.*?)\]$/is', (string)$val, $collect) )
			{
				$elements = array_merge($elements, array(
					$collect[1],
					(object) array(
						'type'=>'attr',
						'key'=>self::_numToInt($collect[2]),
						'val'=>$collect[3],
					)
				));
						
				continue;
			}
			
			//base[index,nextnode]
			if ( preg_match('/^([^\]]*?)\[(\d+), ?([^\]]+)\]$/is', (string)$val, $collect) )
			{
				$elements = array_merge($elements, array(
					(object) array(
						'type'=>'idx',
						'key'=>self::_numToInt($collect[1]),
						'val'=>$collect[2],
					),
					$collect[3]
				));
							
				continue;
			}
			
			//base[index]
			if ( preg_match('/^([^\]]*?)\[(\d+)\]$/is', (string)$val, $collect) )
			{
				$elements[] = (object) array(
					'type'=>'idx',
					'key'=>self::_numToInt($collect[1]),
					'val'=>$collect[2],
				);
				
				continue;
			}
			
			$elements[]=$value;
		}
		
		return $elements;
	}
	
	/**
	 * Convert Numeric to Integer
	 * @access private
	 * @param string $str Any Text
	 * @return int
	 */
	private static function _numToInt ( $str )
	{
		return is_numeric ($str)
			? (int) $str
			: $str;
	}
	
	/**
	 * Return Internal Void value
	 * @access private
	 * @param mixed $val Null|BOOL
	 * @param mixed $data (optional) Any value to pass
	 * @return object
	 */
	private	static function _asVoid ( $val, $data=null )
	{
		return (object) array(
			'_var'=>$val,
			'_data'=>$data
		);
	}
	
	/**
	 * Check that given value is Void
	 * @access private
	 * @param stdClass $val Value to be check
	 * @param mixed $checkto NULL|BOOL
	 * @return bool
	 */
	private static function _is ( $val, $checkto )
	{
		return !$val instanceof stdClass
				|| !property_exists( $val, '_var' )
				|| $val->_var !== $checkto
			? false
			: true;
	}
	
	/**
	 * Preform comparison
	 * @todo Do more logic if you want.
	 * @access private
	 * @param string $expr Expression to be compared with Value
	 * @param mixed $val Data Inner value
	 * @return bool
	 */
	private static function _valExpressions ( $expr, $val )
	{
		return $expr == $val;
	}
	
	/**
	 * Evaluate Root Query expressions
	 * @access private
	 * @param stdClass $expr Expression Object
	 * @param array $roots
	 * @param mixed|array $ary Array Tree
	 * @param int $i Index of root element
	 * @param mixed $value Given Value to update with
	 * @return mixed
	 */
	private static function _evalQueryExprs ( stdClass $expr, &$roots, &$ary, $i, $value )
	{
		if ( $expr->type === 'idx' && isset( $ary[$expr->key] ) )
		{
			for ( $idx=0; $idx<count ($ary[$expr->key]); $idx++ )
			{
				if ( self::_valExpressions ($expr->val, $idx) )
				{
					unset($roots[$i]);
					return self::_toRoot( $roots, $ary[$expr->key][$expr->val], $value );
				}
			}
			
			return self::_asVoid ( false, 1 );
		}

		if ( $expr->type === 'attr' )
		{
			foreach ( $ary as $idx=>$jval )
			{
				if ( !isset ( $jval[$expr->key] )
					|| !self::_valExpressions( $jval[$expr->key], $expr->val ) )
				{
					continue;
				}

				unset($roots[$i]);

				if ( count($roots) === 0 )
				{
					if ( !self::_is ($value, null) )
					{
						$ary[$idx]=$value;
						return self::_asVoid ( true, 'J' );
					}
					
					return $ary[$idx];
				}

				if ( count($roots) && !is_array( $ary[$idx] ) )
				{
					return self::_asVoid ( false, 2 );
				}

				return self::_toRoot( $roots, $ary[$idx], $value );
			}
		}
		
		return self::_asVoid ( false, 3 );
	}
	
	/**
	 * Check and match the current element key
	 * @access private
	 * @param string $val current root value
	 * @param array $roots List of roots
	 * @param array $ary Array Tree
	 * @param mixed $value Value of current element
	 * @return object|array|null|mixed
	 */
	private static function _ArykeyComp ( $val, &$roots, &$ary, $value )
	{		
		if ( !self::_is ( $value, null ) )
		{
			$ary[$val] = $value;
			return self::_asVoid ( true, 'U' );
		}

		if ( count($roots)>1 || !is_array($ary[$val]))
		{
			return self::_asVoid ( false, 4 );
		}

		return $ary[$val];
	}
	
	/**
	 * Walk by the root elements
	 * @access private
	 * @param array $eroots
	 * @param mixed|array $ary Array Data or Some value
	 * @param mixed $value Date to be updated with last element | (null using self::$V_NULL)
	 * @return mixed Mixed Data | $V_FALSE === for Error
	 */
	private static function _toRoot( $eroots, &$ary, $value )
	{
		$roots = array_values($eroots);
		
		if ( count($roots)
			&& (!is_array($ary)
				|| !count($ary))  )
		{
			return self::_asVoid ( false, 5 );
		}
		
		if ( !count($roots ) )
		{
			if ( !self::_is ($value, null) )
			{
				$ary=$value;
				return self::_asVoid ( true,'N' );
			}

			return $ary;
		}

		foreach ( $roots as $i=>$val )
		{			
			if ( $val instanceof stdClass && isset($val->type) )
			{
				return self::_evalQueryExprs( $val, $roots, $ary, $i, $value );
			}
			
			if ( !array_key_exists( $val, $ary ) )
			{
				if ( !!is_array( $ary[$val] ) )
				{
					return self::_ArykeyComp( $val, $roots, $ary, $value );
				}
				
				if ( !self::_is ($value, null) )
				{
					$ary[$val]=$value;
					return self::_asVoid ( true,'A' );
				}

				return $ary[$val];
			}

			unset($roots[$i]);
			return self::_toRoot( $roots, $ary[$val], $value );
		}

		return self::_asVoid ( false, 6 );
	}
	
	/**
	* @link http://uk1.php.net/array_walk_recursive#114574
	* @link https://github.com/gajus/marray/blob/master/src/marray.php#L116
	* @param array The input array.
	* @param callable $callback Function must return boolean value indicating whether to remove the node.
	* @return array
	*/
    private static function walkRecursiveRemove (array &$array, callable $callback)
	{
		foreach ($array as $k => $v)
		{
			if (is_array($v)) {
				$array[$k] = self::walkRecursiveRemove($v, $callback);
			} else {
				if ($callback($v, $k)) {
					unset($array[$k]);
				}
			}
		}
		
		return $array;
	}
	
	/**
	 * Verify data and return based on Void
	 * @access private
	 * @param mixed $data Data to be checked
	 * @param bool $asBool True will return as Bool
	 * @param mixed $emptyValue (optional) Value on Null (default null)
	 * @return mixed array|null|bool
	 */
	private static function _asVoidData ( $data, $asBool=false, $emptyValue=null )
	{		
		return $data === null || self::_is ($data, false) || self::_is ($data, null)
			? ($asBool ? true : $emptyValue)
			: ($asBool ? false : $data);
	}
	
	/**
	 * Get Element(s) of Array using given path
	 * @access public
	 * @param string $query Path to the Array
	 * @param array $params (optional) key=>value parameters to replace query string
	 * @param mixed $emptyValue (optional) Value on Null (default null)
	 * @return mixed Null on not Found | array/value
	 */
	public static function find ( $query, $ary, array $params=array(), $emptyValue=null )
	{
		$roots = self::_pathToElements ( $query, $params );
		
		return self::_asVoidData (
			self::_toRoot( $roots, $ary, self::_asVoid (null) ),
			false,
			$emptyValue
		);
	}
	
	/**
	 * Update Element of Array using given path
	 * @access public
	 * @param string $query Path to the Array
	 * @param array $ary Array Data Object
	 * @param mixed $value New Value
	 * @param array $params (optional) key=>value parameters to replace in query string
	 * @return bool True on Updated | False on Failed
	 */
	public static function update ( $query, &$ary, $value, array $params=array() )
	{
		$roots = self::_pathToElements ( $query, $params );
		return !self::_asVoidData (
			self::_toRoot( $roots, $ary, $value ),
			true
		);
	}
	
	/**
	 * Check array Difference (Recursive)
	 * @copyright (c) 2009, Firegun
	 * @link http://php.net/manual/en/function.array-diff.php#91756
	 * @param array $ary1 Array 1
	 * @param array $ary2 Array 2 compare with Array 1
	 * @return array
	 */
	public static function difference ( $ary1, $ary2 )
	{
		$aReturn = array();
		
		foreach ($ary1 as $key => $value)
		{
			if ( array_key_exists($key, $ary2) )
			{
				if (is_array($value))
				{
					$diffAry1 = self::difference($value, $ary2[$key]);
					
					if ( count($diffAry1) )
					{
						$aReturn[$key] = $diffAry1;
					}
				}
				else
				{
					if ( $value != $ary2[$key] )
					{
						$aReturn[$key] = $value;
					}
				}
			}
			else
			{
				$aReturn[$key] = $value;
			}
		}
		
		return $aReturn;
	} 

	/**
	 * Remove element(s)
	 * @param string $query Path to the Array
	 * @param array $ary Array Data Object
	 * @param array $params (optional) key=>value parameters to replace query string
	 * @return boolean
	 */
	public static function delete ( $query, &$ary, array $params=array() )
	{
		if ( !self::update( $query, $ary, self::_asVoid (-1, -1), $params ) )
		{
			return false;
		}
		
		$old = $ary;

		self::walkRecursiveRemove( $ary, function($v) { return self::_is ($v, -1); });
		
		return (bool) count( self::difference( $old,$ary ) );
	}
	
	/**
	 * Check that query is valid and Array structure exists
	 * @access public
	 * @param string $query Path to the Array
	 * @param array $ary Array Data Object
	 * @param array $params (optional) key=>value parameters to replace query string
	 * @return bool
	 */
	public static function exists ( $query, array $ary, array $params=array() )
	{
		$roots = self::_pathToElements ( $query, $params );
		
		return !self::_asVoidData (
			self::_toRoot( $roots, $ary, self::_asVoid (null) ),
			true
		);
	}	
}