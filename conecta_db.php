<?php
	function conecta_db() {
		$db_name = "bookington";
		$user = "root";
		$pass = "";
		$server = "localhost";

		try {
			$conexao = new mysqli($server, $user, $pass, $db_name);
			return $conexao;
		} catch (mysqli_sql_exception $e) {
			return false;
		}
	}
?>