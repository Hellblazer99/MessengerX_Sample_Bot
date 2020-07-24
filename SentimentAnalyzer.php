<?php

	require_once('SentimentAnalyzer.class.php');

	class SentimentAnalyzerTest
	{
		protected $sentimentAnalyzer;
		public function __construct(SentimentAnalyzer $sentimentAnalyzer)
		{
			$this->sentimentAnalyzer = $sentimentAnalyzer;
		}

		public function trainAnalyzer($testDataLocation, $testDataType, $testDataAmount)
		{
			return $this->sentimentAnalyzer->insertTestData($testDataLocation, $testDataType, $testDataAmount);
		}

		public function analyzeSentence($sentence)
		{
			return $this->sentimentAnalyzer->analyzeSentence($sentence);
		}

		public function analyzeDocument($document)
		{
			return $this->sentimentAnalyzer->analyzeDocument($document);
		}
	}
?>

