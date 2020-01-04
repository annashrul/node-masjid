<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Jakarta');
    }

    public function update_apk() {
        $response = array();
        $app = $_POST['app'];

        $get_update = $this->m_crud->get_data("update_apk", "version, url", "id_app='".$app."'");

        if ($get_update != null) {
            $response['status'] = true;
            $response['data'] = $get_update;
        } else {
            $response['status'] = false;
        }

        echo json_encode($response);
    }

    public function cek_koneksi() {
        echo json_encode(array('status'=>true));
    }
	
	public function get_produk(){
		print_r($this->m_website->curl_store_online('get_produk'));
		//print_r($this->m_website->request_api());
	}
	
	public function kartu_stock(){
		ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '1024M');
		
		ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
		ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
		
        $where = null;
        $where2 = null;
        $group = null;
        $filter_table = null;
        $tgl_akhir = date('Y-m-d');
        $tgl_awal = date('Y-m-d');

        $where_stock = "kd_brg = br.kd_brg";

        $this->session->set_userdata('search', array(
			'any' => isset($_POST['any'])?$_POST['any']:null, 
			'filter' => isset($_POST['filter'])?$_POST['filter']:null, 
			'filter2' => isset($_POST['filter2'])?$_POST['filter2']:null, 
			'date1'=>isset($_POST['tgl_awal'])?$_POST['tgl_awal']:null, 
			'date2'=>isset($_POST['tgl_akhir'])?$_POST['tgl_akhir']:null, 
			'lokasi' => isset($_POST['lokasi'])?$_POST['lokasi']:null, 
			'supplier' => isset($_POST['supplier'])?$_POST['supplier']:null, 
			'kd_brg' => isset($_POST['kd_brg'])?$_POST['kd_brg']:null,
			'in_brg' => isset($_POST['in_brg'])?$_POST['in_brg']:null,
			'page' => isset($_POST['page'])?$_POST['page']:null,
			'per_page' => isset($_POST['per_page'])?$_POST['per_page']:null
		));
        
        $search = $this->session->search['any']; 
		$lokasi = $this->session->search['lokasi']; 
		$supplier = $this->session->search['supplier']; 
		$filter = $this->session->search['filter']; 
		$filter2 = $this->session->search['filter2']; 
		$date1 = $this->session->search['date1']; 
		$date2 = $this->session->search['date2']; 
		$kd_brg = $this->session->search['kd_brg'];
		$kd_brg_in = json_decode($this->session->search['in_brg'], true);
		$page = $this->session->search['page'];
		$per_page = $this->session->search['per_page'];
        
		if (isset($date1) && $date1 != null) {
            $tgl_awal = $date1;
        }
		if (isset($date2) && $date2 != null) {
            $tgl_akhir = $date2;
        }

        if(isset($supplier) && $supplier != null){ ($where==null)?null:$where.=" AND "; $where.="br.Group1='".$supplier."'";}
        if(isset($lokasi) && $lokasi != null){
            $having = "(select count(kd_brg) from kartu_stock where lokasi = '".$lokasi."' and kartu_stock.kd_brg=br.kd_brg) > 0";
            $where_stock .= " and kartu_stock.lokasi = '".$lokasi."' ";
            $data['lokasi'] = $lokasi;
        } else { 
            $having = "(select count(kd_brg) from kartu_stock where lokasi = 'IDK-ONLINE' and kartu_stock.kd_brg=br.kd_brg) > 0";
            $where_stock .= " and kartu_stock.lokasi = 'IDK-ONLINE' ";
            $data['lokasi'] = 'IDK-ONLINE';
        }

        if ($filter == '>') {
            ($having!=null)?$having.=' and ':null;
            $having .= "isnull((select sum(stock_in - stock_out) from kartu_stock where kartu_stock.kd_brg=br.kd_brg and lokasi NOT IN ('MUTASI', 'Retur') AND ".$where_stock." and left(convert(varchar, tgl, 120), 10) <= '".$tgl_akhir."'),0) > 0";
        } else if ($filter == '<') {
            ($having!=null)?$having.=' and ':null;
            $having .= "isnull((select sum(stock_in - stock_out) from kartu_stock where kartu_stock.kd_brg=br.kd_brg and lokasi NOT IN ('MUTASI', 'Retur') AND ".$where_stock." and left(convert(varchar, tgl, 120), 10) <= '".$tgl_akhir."'),0) < 0";
        } else if ($filter == '=') {
            ($having!=null)?$having.=' and ':null;
            $having .= "isnull((select sum(stock_in - stock_out) from kartu_stock where kartu_stock.kd_brg=br.kd_brg and lokasi NOT IN ('MUTASI', 'Retur') AND ".$where_stock." and left(convert(varchar, tgl, 120), 10) <= '".$tgl_akhir."'),0) = 0";
        }

        if(isset($kd_brg) && $kd_brg != null){ ($where==null)?null:$where.=" AND "; $where.="br.kd_brg = '".$kd_brg."'"; }
        
		if(isset($kd_brg_in) && $kd_brg_in != null){ ($where==null)?null:$where.=" AND "; $where.="br.kd_brg in (".implode(", ",$kd_brg_in).")"; }
        
		if(isset($search) && $search != null){ ($where==null)?null:$where.=" AND "; $where.="(br.kd_brg like '%".$search."%' or br.nm_brg like '%".$search."%')"; }
        //if(isset($filter) && $filter != null){ ($where==null)?null:$where.=""; $where.=" and ks.kd_brg=br.kd_brg "; $where.=$where2; $group=" GROUP BY br.kd_brg, br.barcode, br.nm_brg, br.satuan, gr1.Nama HAVING SUM(ks.stock_in-ks.stock_out)".$filter."0"; $filter_table="Kartu_stock ks"; }
		
        $page = ($page==null?1:$page);

        $config['base_url'] = base_url().strtolower($this->control).'/'.$function.'/'.($action!=null?$action:'-').'/';
        $config['total_rows'] = $this->m_crud->count_data_join_over('barang br', "br.kd_brg", "Kartu_stock ks", "ks.kd_brg=br.kd_brg", ($where==null?'':$where), 'br.kd_brg ASC', "br.kd_brg", 0, 0, $having);
        $config['per_page'] = ($per_page==null?10:$per_page);
        //$config['attributes'] = array('class' => ''); //attributes anchors
        $config['first_url'] = $config['base_url'];
        $config['num_links'] = 5;
        $config['use_page_numbers'] = TRUE;
        //$config['display_pages'] = FALSE;
        $config['full_tag_open'] = '<ul class="pagination pagination-sm">';
        $config['first_tag_open'] = '<li>'; $config['first_link'] = '&laquo;'; $config['first_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li>'; $config['prev_link'] = '&lt;'; $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a href="#"> '; $config['cur_tag_close'] = '</a></li>';
        $config['num_tag_open'] = '<li>'; $config['num_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>'; $config['next_link'] = '&gt;';  $config['next_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>'; $config['last_link'] = '&raquo;'; $config['last_tag_close'] = '</li>';
        $config['full_tag_close'] = '</ul>';
        $this->pagination->initialize($config);

        $stock_awal = "isnull((select sum(stock_in - stock_out) from kartu_stock where kartu_stock.kd_brg=br.kd_brg and lokasi NOT IN ('MUTASI', 'Retur') AND ".$where_stock." and tgl < '".$tgl_awal." 00:00:00'),0) as stock_awal";
        $stock_masuk = "isnull((select sum(stock_in) from kartu_stock where kartu_stock.kd_brg=br.kd_brg and lokasi NOT IN ('MUTASI', 'Retur') AND ".$where_stock." and tgl >= '".$tgl_awal." 00:00:00' and tgl <= '".$tgl_akhir." 23:59:59'),0) as stock_masuk";
        $stock_keluar = "isnull((select sum(stock_out) from kartu_stock where kartu_stock.kd_brg=br.kd_brg and lokasi NOT IN ('MUTASI', 'Retur') AND ".$where_stock." and tgl >= '".$tgl_awal." 00:00:00' and tgl <= '".$tgl_akhir." 23:59:59'),0) as stock_keluar";
		
		$data['report'] = $this->m_crud->select_limit_join('barang br', "br.kd_brg, br.barcode, br.nm_brg, br.satuan, gr1.Nama, ".$stock_masuk.", ".$stock_keluar.", ".$stock_awal."", array(array('table'=>'Group1 gr1', 'type'=>'LEFT'), array('table'=>'Kartu_stock ks', 'type'=>'LEFT')), array("br.Group1=gr1.Kode", "ks.kd_brg=br.kd_brg"), ($where==null?'':$where), 'gr1.Nama ASC, br.kd_brg ASC', "br.kd_brg, br.barcode, br.nm_brg, br.satuan, gr1.Nama", ($page-1)*$config['per_page']+1, ($config['per_page']*$page), $having);
        
        $total = $this->m_crud->join_data('barang br', "".$stock_masuk.", ".$stock_keluar.", ".$stock_awal."", array(array('table'=>'Group1 gr1', 'type'=>'LEFT')), array("br.Group1=gr1.Kode"), ($where==null?'':$where), null, "br.kd_brg", 0, 0, $having);
		
        $tstaw = 0; $tstma = 0; $tstke = 0; $tstak = 0;

        foreach ($total as $row) {
            $tstaw = $tstaw + (int)$row['stock_awal'];
            $tstma = $tstma + (int)$row['stock_masuk'];
            $tstke = $tstke + (int)$row['stock_keluar'];
            $tstak = $tstak + ((int)$row['stock_awal']+(int)$row['stock_masuk']-(int)$row['stock_keluar']);
        }

        $data['tstaw'] = $tstaw;
        $data['tstma'] = $tstma;
        $data['tstke'] = $tstke;
        $data['tstak'] = $tstak;
		
		echo json_encode(array('status'=>1, 'result'=>$data, 'total_rows'=>$config['total_rows'], 'tgl_awal'=>$tgl_awal, 'tgl_akhir'=>$tgl_akhir));
	}
	
	function detail_by_transaksi() {
        $tgl_awal = date("Y-m-d");
        $tgl_akhir = date("Y-m-d");
        $kd_brg = '';
		$lokasi='IDK-ONLINE';
		
        $detail_list = '';

        if (isset($_POST['tgl_awal']) && $_POST['tgl_awal']!=null) {
            $tgl_awal = $_POST['tgl_awal'];
        }
		if (isset($_POST['tgl_akhir']) && $_POST['tgl_akhir']!=null) {
            $tgl_akhir = $_POST['tgl_akhir'];
        }
		if (isset($_POST['kd_brg']) && $_POST['kd_brg']!=null) {
            $kd_brg = $_POST['kd_brg'];
        }
		if (isset($_POST['lokasi']) && $_POST['lokasi']!=null) {
            $lokasi = $_POST['lokasi'];
        }

        $q_title = $this->m_crud->get_data("Kartu_stock ks, Lokasi lk, barang br", "lk.Nama, br.kd_brg, br.barcode, br.nm_brg", "ks.lokasi=lk.Kode AND ks.kd_brg=br.kd_brg AND ks.kd_brg = '".$kd_brg."' AND ks.lokasi = '".$lokasi."'");
        $q_detail = $this->m_crud->read_data("Kartu_stock", "kd_trx, tgl, keterangan, stock_in, stock_out", "kd_brg = '".$kd_brg."' AND lokasi = '".$lokasi."' AND tgl >= '".$tgl_awal." 00:00:00' and tgl <= '".$tgl_akhir." 23:59:59' ", "tgl asc");
        
        $title = [$q_title['Nama'], $q_title['kd_brg'], $q_title['barcode'], $q_title['nm_brg']];
		
        echo json_encode(array('list' => $q_detail, 'title' => $title, 'tgl_awal'=>$tgl_awal, 'tgl_akhir'=>$tgl_akhir));
    }
	
	public function promo_double_diskon($kode){
		$barang = $this->m_crud->get_data('barang', 'hrg_jual_1', "kd_brg = '".$kode."'");
		$promo = $this->m_crud->get_data('master_promo', 'pildiskon, diskon, diskon2', "kode = '".$kode."' and (periode = '1' or (periode = '0' and dariTgl <= '".date('Y-m-d')." 00:00:00' and sampaiTgl >= '".date('Y-m-d')." 00:00:00'))"); 
		$harga = 0;
		if($promo['pildiskon']=='%'){
			$harga = $this->m_website->multi_diskon($barang['hrg_jual_1'], array($promo['diskon'],$promo['diskon2']));
		} else if($promo['pildiskon']=='money'){
			$harga = $barang['hrg_jual_1'] - $promo['diskon'];
		}
		echo $harga;
	}
	
    public function check_article($article, $nm_brg, $hrg_jual, $supplier) {
        $article = base64_decode($article);
        $nm_brg = base64_decode($nm_brg);
        $hrg_jual = base64_decode($hrg_jual);
        $supplier = base64_decode($supplier);

        $count_data = $this->m_crud->count_read_data("barang", "kd_brg", "Deskripsi = '".$article."'");

        if ($count_data == 0) {
            $kode_barang = $this->m_website->generate_kode_barang('PL58', 4);
            $barcode = $this->m_website->generate_barcode('2525', 4);
            
            $data = array(
                'kd_brg' => $kode_barang,
                'barcode' => $barcode,
                'nm_brg' => $nm_brg,
                'Deskripsi' => $article,
                'hrg_jual_1' => $hrg_jual,
                'kel_brg' => 'PL58',
                'Group1' => $supplier,
                'Group2' => 'PL',
                'kategori' => 'Non Paket',
                'satuan' => 'PCS',
                'gambar' => '-',
                'Jenis' => 'Barang Dijual',
                'tgl_input' => date('Y-m-d H:i:s')
            );
            $this->m_crud->create_data("barang", $data);
        }

        $get_data = $this->m_crud->get_data("barang", "kd_brg, barcode, nm_brg, Deskripsi, Group1, hrg_jual_1", "Deskripsi = '".$article."'");

        echo json_encode(array('status'=>$count_data, 'data'=>$get_data));
    }
    
    public function get_supplier() {
		$data = $this->m_crud->read_data("Supplier", "Kode, Nama");
		
		echo json_encode($data);
	}
	
	public function get_nama_supplier($kode) {
		$kode = base64_decode($kode);
		$data = $this->m_crud->get_data("Supplier", "Nama", "Kode = '".$kode."'");
		echo $data['Nama'];
	}
	
	public function data_customer() {
        $param = $_POST['param'];
        if ($param == 'add') {
            $max_kode = $this->m_crud->get_data("Customer", "MAX(CONVERT(INTEGER, RIGHT(kd_cust, 6))) kd_cust");
            $kode = "1" . sprintf('%06d', $max_kode['kd_cust'] + 1);
            $this->m_crud->create_data("Customer", array(
                'kd_cust' => $kode,
                'Nama' => $_POST['nama'],
                'ol_code' => $_POST['kode'],
                'diskon' => 0,
                'status' => '1',
                'tlp1' => $_POST['tlp'],
                'tgl_ultah' => $_POST['tgl_lahir'],
                'alamat' => $_POST['alamat'],
                'Cust_Type' => 'UMUM'
            ));
        } else if ($param == 'edit') {
            $this->m_crud->update_data("Customer", array(
                'Nama' => $_POST['nama'],
                'tlp1' => $_POST['tlp'],
                'tgl_ultah' => $_POST['tgl_lahir']
            ), "ol_code = '".$_POST['kode']."'");
        }
    }

    public function update_data() {
        $response = array();
        $data = json_decode(base64_decode($_POST['data']), true);

        $tanggal = date('Y-m-d');
        $waktu = date('H:i:s');
        $table = $data['table'];
        $update = $data['update'];
        $id = $data['id'];

        $this->db->trans_begin();

        if ($table == 'barang') {
            $read_lokasi = $this->m_crud->read_data('Lokasi', 'Kode, Nama, server, db_name', "Kode<>'HO'");
            foreach ($update as $item) {
                $master = array($item['col']=>$item['data']);

                $this->m_crud->update_data($table, $master, "kd_brg='".$id."'");

                foreach ($read_lokasi as $item_lokasi) {
                    $log = array(
                        'type' => 'U',
                        'table' => $table,
                        'data' => $master,
                        'condition' => "kd_brg='".$id."'"
                    );

                    $data_log = array(
                        'lokasi' => $item_lokasi['Kode'],
                        'hostname' => $item_lokasi['server'],
                        'db_name' => $item_lokasi['db_name'],
                        'query' => json_encode($log)
                    );
                    $this->m_website->insert_log_api($data_log);
                }
            }
        } else if ($table == 'kurir') {
            foreach ($update as $item) {
                $this->m_crud->update_data($table, array($item['col']=>$item['data']), "id_kurir='".$id."'");
            }
        } else if ($table == 'master_to') {
            $set_update = array($update['col']=>$update['data']);
            if ($update['col'] == 'meja') {
                $master = $this->m_crud->get_join_data($table, "no_meja, isnull(sl.nama, 'UMUM') kd_waitres, ".$table.".lokasi, atas_nama", array(array('table'=>'sales sl', 'type'=>'LEFT')), array($table.'.kd_waitres=sl.kode'), "no_to='".$id."'");

                $kitchen_print = array();
                $get_printer = $this->m_crud->read_data("kitchen_printer", "id_printer, konektor, vid, pid, ip");

                $nama_meja = $this->m_crud->get_data("data_meja", "(nama_area + ' ' + nama_meja) text", "id_meja='".$update['data']."'")['text'];

                foreach ($get_printer as $item) {
                    array_push($kitchen_print, array('server'=>$item['ip'], 'konektor'=>$item['konektor'], 'vid'=>$item['vid'], 'pid'=>$item['pid'], 'kd_trx'=>$id, 'tanggal'=>$tanggal, 'waktu'=>$waktu, 'waitres'=>$master['kd_waitres'], 'atas_nama'=>$master['atas_nama'], 'no_meja'=>$master['no_meja'], 'no_meja_2'=>$nama_meja, 'status'=>'Pindah Meja', 'list'=>array()));
                }

                if ($master['meja'] != $update['data']) {
                    $get_det_to = $this->m_crud->join_data("detail_to dt", "dt.kcp, dt.kd_brg, br.nm_brg, dt.qty, dt.urutan", "barang br", "br.kd_brg=dt.kd_brg", "dt.status='P' AND dt.no_to='".$id."'");
                    foreach($get_det_to as $item) {
                        if ($item['kcp'] != '' || $item['kcp'] != null) {
                            $item['status_kcp'] = '0';
                            $found_key = array_search($item['kcp'], array_column($get_printer, 'id_printer'));
                            array_push($kitchen_print[$found_key]['list'], array('kd_brg' => $item['kd_brg'], 'nm_brg' => $item['nm_brg'], 'qty' => $item['qty'], 'urutan'=>$item['urutan']));
                        }
                    }
                }

                $set_update['no_meja'] = $nama_meja;

                $response['print'] = $this->m_website->insert_log_print($kitchen_print, 'meja', $master['lokasi']);
            }

            $this->m_crud->update_data($table, $set_update, "no_to='".$id."'");
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $response['status'] = false;
            $response['pesan'] = 'Data gagal disimpan';
        } else {
            $this->db->trans_commit();
            $response['status'] = true;
            $response['pesan'] = 'Data berhasil disimpan';
        }

        echo json_encode($response);
    }

    /*API TO*/
    public function login() {
        $response = array();
        $username = $_POST['username'];
        $password = md5($_POST['password']);
        $lokasi_login = $_POST['lokasi'];

        $cek = $this->m_website->login($username, $password);
        if($cek <> 0) {
            $response['status'] = true;

            $data_login = array(
                'user_id' => $cek->user_id,
                'nama_user' => $cek->user_id
            );

            $lokasi = json_decode($cek->lokasi, true);

            $lokasi_in = array();
            foreach ($lokasi['lokasi_list'] as $item) {
                array_push($lokasi_in, '\''.$item['kode'].'\'');
            }

            $get_lokasi = $this->m_crud->read_data('lokasi', "kode, nama_toko nama, ket alamat, serial, nama header1, ket header2, kota header3, web header4, footer1, footer2, footer3, footer4", "kode in (".implode(',', $lokasi_in).")");

            $lokasi_list = array();
            $data_lokasi = array();
            foreach ($get_lokasi as $item) {
                $head_foot = array(
                    'header1' => $item['header1'],
                    'header2' => $item['header2'],
                    'header3' => $item['header3'],
                    'header4' => $item['header4'],
                    'footer1' => $item['footer1'],
                    'footer2' => $item['footer2'],
                    'footer3' => $item['footer3'],
                    'footer4' => $item['footer4']
                );
                array_push($lokasi_list, array(
                    'kode' => $item['kode'],
                    'nama' => $item['nama'],
                    'alamat' => $item['alamat'],
                    'serial' => $item['serial'],
                    'header_footer' => $head_foot
                ));
                array_push($data_lokasi, $item['kode']);
            }

            if ($lokasi_login != '') {
                if (in_array($lokasi_login, $data_lokasi)) {
                    $response['select_lokasi'] = false;
                } else {
                    $response['select_lokasi'] = true;
                }
            } else {
                $response['select_lokasi'] = true;
            }

            $data_login['lokasi'] = $lokasi_list;

            $response['pesan'] = 'Login berhasil';
            $response['data'] = $data_login;
        } else{
            $response['status'] = false;
            $response['pesan'] = 'Username / Password tidak sesuai!';
            $response['data'] = array();
        }

        echo json_encode($response);
    }

    public function cek_no_meja($meja=null, $edit=null) {
        $response = array();

        if ($meja == null) {
            $no_meja = $_POST['meja'];
        } else {
            $no_meja = $meja;
        }

        $where = "status='P' and meja='".$no_meja."'";

        if (isset($_POST['edit']) || $edit != null) {
            if (isset($_POST['edit'])) {
                $edit = $_POST['edit'];
            }
            $where .= " and no_to <> '".$edit."'";
        }

        $get_data = $this->m_crud->get_data("master_to", "no_to", $where);

        if ($get_data != null) {
            $response['status'] = false;
            $response['pesan'] = 'Nomor meja tidak tersedia';
        } else {
            $response['status'] = true;
        }

        if ($meja != null) {
            return $response['status'];
        } else {
            echo json_encode($response);
        }
    }

    public function get_data($table=null, $perpage=10, $page=1) {
        $response = array();
        $where = isset($_POST['where'])?$_POST['where']:null;

        if (isset($_POST['lokasi'])) {
            $lokasi = $_POST['lokasi'];
        } else {
            $lokasi = null;
        }

        $start = ($page-1)*$perpage+1;
        $end = $perpage*$page;

        if ($table != null) {
            $order = array(
                'barang' => 'nm_brg ASC',
                'customer' => 'kd_cust ASC',
                'kel_brg' => 'nm_kel_brg ASC',
                'group2' => 'nama ASC',
                'request_pesanan' => 'id_request ASC',
                'sales' => 'Nama asc',
                'area' => 'nama asc'
            );

            if ($table == 'sales') {
                $where .= ($where==null?'':' AND ');
                $where .= "status = '1' AND lokasi = '".(isset($_POST['lokasi'])?$_POST['lokasi']:$this->config->item('lokasi'))."'";
            } else if ($table == 'area') {
                $where .= ($where==null?'':' AND ');
                $where .= "lokasi = '".(isset($_POST['lokasi'])?$_POST['lokasi']:$this->config->item('lokasi'))."'";
            } else if ($table == 'kel_brg') {
                $where .= ($where==null?'':' AND ');
                $where .= "status='1'";
            } else if ($table == 'barang') {
                $where .= ($where==null?'':' AND ');
                $where .= "jenis='Barang Dijual'";
            }

            $read_data = $this->m_crud->select_limit($table, "*", $where, $order[strtolower($table)], null, $start, $end);

            if ($read_data != null) {
                $response['status'] = true;
                $response['data'] = $this->m_website->tambah_data($table, $read_data, $lokasi);
            } else {
                $response['status'] = false;
                $response['pesan'] = 'Data tidak tersedia';
            }
        } else {
            $response['status'] = false;
            $response['pesan'] = 'Parameter tidak lengkap';
        }

        echo json_encode($response);
    }

    public function list_produk($per_page=10, $page=1) {
        $response = array('status' => true);
        $where = null;
        $filter = json_decode($_POST['filter'], true);
        $type = $_POST['type'];
        $online = $_POST['online'];
        if (isset($_POST['lokasi'])) {
            $lokasi = $_POST['lokasi'];
        } else {
            $lokasi = null;
        }

        if ($type == 'barang') {
            $where = "Jenis='Barang Dijual'";

            if (isset($filter['cari']) && $filter['cari']!='') {
                $where .= ($where!=null?" AND ":"")."(br.kd_brg like '%".$filter['cari']."%' OR br.nm_brg like '%".$filter['cari']."%' OR br.nm_brg like '%".$filter['cari']."%' OR kb.nm_kel_brg like '%".$filter['cari']."%')";
            }

            if (isset($filter['kelompok']) && $filter['kelompok']!='') {
                $where .= ($where!=null?" AND ":"")."br.kel_brg = '".$filter['kelompok']."'";
            }

            if (isset($filter['fav']) && $filter['fav']!='') {
                $where .= ($where!=null?" AND ":"")."br.fav = '1'";
            }

            if (isset($online)) {
                $where .= ($where!=null?" AND ":"")."br.online = '1'";
            }

            $get_data = $this->m_crud->select_limit_join("barang br", 'br.kd_brg, br.nm_brg, br.hrg_jual_1, br.gambar, br.kcp', "kel_brg kb", "kb.kel_brg=br.kel_brg", $where, "br.nm_brg ASC", null, ($page-1)*$per_page+1, $per_page*$page);
            if ($get_data == null) {
                $response['status'] = false;
            }
            $response['data'] = $this->m_website->tambah_data('barang', $get_data, $lokasi);
        } else if ($type == 'kelompok') {
            $where .= "status='1'";
            $get_data = $this->m_crud->select_limit("kel_brg", "kel_brg, nm_kel_brg", $where, "nm_kel_brg ASC", null, ($page-1)*$per_page+1, $per_page*$page);
            if ($get_data == null) {
                $response['status'] = false;
            }
            $response['data'] = $get_data;
        } else {
            $response['status'] = false;
            $response['pesan'] = 'Parameter tidak terdaftar!';
        }

        echo json_encode($response);
    }

    public function simpan_to() {
        $response = array();
        $data = json_decode($_POST['data'], true);
        $param = $_POST['param'];

        if (is_array($data)) {
            $master = $data['master'];
            $detail = $data['detail'];
            $tanggal = date('Y-m-d');
            $waktu = date('H:i:s');

            $this->db->trans_begin();

            if ($param == 'new') {
                $pesan = 'Pesanan Baru';
                $param_meja = null;
                $lokasi = $master['lokasi'];
                $waitres = $this->m_crud->get_data("sales", "nama", "kode='".$master['kd_waitres']."'")['nama'];
            } else if ($param == 'update') {
                $pesan = 'Pesanan Tambahan';
                $kd_trx = $_POST['kd_trx'];
                $param_meja = $kd_trx;
                $lokasi = $this->m_crud->get_data("master_to", "lokasi", "no_to='".$kd_trx."'")['lokasi'];
                $waitres = $this->m_crud->get_data("sales", "nama", "kode='".$master['kd_waitres']."'")['nama'];
            }

            $kode_to = $this->m_website->generate_kode('TO', $data['master']['lokasi'], date('ymd', strtotime($tanggal)));

            $master['no_to'] = $kode_to;
            $master['tanggal'] = $tanggal;
            $master['waktu'] = $waktu;
            $master['gabung'] = '';
            $master['status'] = 'P'; //status S=Success, P=On Process, C=Cancel
            //type_order D=Dinein, T=Take Away, O=Online Order
            $no_meja = $master['meja'];

            $cek_meja = $this->cek_no_meja($no_meja, $param_meja);

            $kitchen_print = array();
            $get_printer = $this->m_crud->read_data("kitchen_printer", "id_printer, konektor, vid, pid, ip");
            foreach ($get_printer as $item) {
                array_push($kitchen_print, array('server'=>$item['ip'], 'konektor'=>$item['konektor'], 'vid'=>$item['vid'], 'pid'=>$item['pid'], 'kd_trx'=>$kode_to, 'tanggal'=>$tanggal, 'waktu'=>$waktu, 'waitres'=>$waitres, 'atas_nama'=>$master['atas_nama'], 'no_meja'=>$master['no_meja'], 'keterangan'=>$master['keterangan'], 'status'=>$pesan, 'list'=>array()));
            }

            if ($param == 'new') {
                $this->m_crud->create_data("master_to", $master);

                /*Insert log*/
                $log = array(
                    'type' => 'I',
                    'table' => "master_to",
                    'data' => $master,
                    'condition' => ""
                );

                $data_log = array(
                    'lokasi' => $this->config->item('lokasi'),
                    'hostname' => '-',
                    'db_name' => '-',
                    'query' => json_encode($log)
                );
                $this->m_website->insert_log_tr($data_log);
                /*End insert log*/

                foreach ($detail as $key => $item) {
                    $item['status_kcp'] = '1';
                    if ($item['kcp'] != '' || $item['kcp'] != null) {
                        $item['status_kcp'] = '0';
                        $found_key = array_search($item['kcp'], array_column($get_printer, 'id_printer'));
                        array_push($kitchen_print[$found_key]['list'], array('kd_brg' => $item['kd_brg'], 'nm_brg' => $item['nm_brg'], 'ket'=>$item['ket'], 'qty' => $item['qty'], 'urutan'=>$item['urutan'], 'status' => 'Pesanan Baru'));
                    }
                    unset($item['nm_brg']);
                    $item['no_to'] = $kode_to;
                    $item['tanggal'] = $tanggal;
                    $item['waktu'] = $waktu;
                    $item['kd_waitres'] = $master['kd_waitres'];
                    $item['status'] = 'P'; //status S=Success, P=On Process, X=Split Bill, C=Cancel
                    $this->m_crud->create_data("detail_to", $item);

                    /*Insert log*/
                    $log = array(
                        'type' => 'I',
                        'table' => "detail_to",
                        'data' => $item,
                        'condition' => ""
                    );

                    $data_log = array(
                        'lokasi' => $this->config->item('lokasi'),
                        'hostname' => '-',
                        'db_name' => '-',
                        'query' => json_encode($log)
                    );
                    $this->m_website->insert_log_tr($data_log);
                    /*End insert log*/
                }
            } else if ($param == 'update') {
                foreach ($detail as $key => $item) {
                    $item['status_kcp'] = '1';
                    $status = 'P';
                    if ($item['kcp'] != '' || $item['kcp'] != null) {
                        $item['status_kcp'] = '0';
                        $found_key = array_search($item['kcp'], array_column($get_printer, 'id_printer'));
                        if ($item['qty'] < 0) {
                            $status_print = 'Cancel';
                            $status = 'S';
                        } else {
                            $status_print = 'Pesanan Baru';
                            $status = 'P';
                        }
                        array_push($kitchen_print[$found_key]['list'], array('kd_brg' => $item['kd_brg'], 'nm_brg' => $item['nm_brg'], 'ket'=>$item['ket'], 'qty' => abs($item['qty']), 'urutan'=>$item['urutan'], 'status' => $status_print));
                    }
                    unset($item['nm_brg']);
                    $item['no_to'] = $kd_trx;
                    $item['tanggal'] = $tanggal;
                    $item['waktu'] = $waktu;
                    $item['kd_waitres'] = $master['kd_waitres'];
                    $item['status'] = $status; //status S=Success, P=On Process, X=Split Bill, C=Cancel
                    $this->m_crud->create_data("detail_to", $item);

                    /*Insert log*/
                    $log = array(
                        'type' => 'I',
                        'table' => "detail_to",
                        'data' => $item,
                        'condition' => ""
                    );

                    $data_log = array(
                        'lokasi' => $this->config->item('lokasi'),
                        'hostname' => '-',
                        'db_name' => '-',
                        'query' => json_encode($log)
                    );
                    $this->m_website->insert_log_tr($data_log);
                    /*End insert log*/
                }
            }

            if ($this->db->trans_status() === FALSE || $cek_meja == false) {
                $this->db->trans_rollback();
                $response['status'] = false;
                if ($cek_meja == false) {
                    $response['pesan'] = 'Nomor meja tidak tersedia';
                } else {
                    $response['pesan'] = 'Transaksi gagal disimpan';
                }
            } else {
                $response['status'] = true;
                $response['pesan'] = 'Transaksi berhasil disimpan';
                $response['print'] = $this->m_website->insert_log_print($kitchen_print, 'to', $lokasi);
                $this->db->trans_commit();
            }
        } else {
            $response['status'] = false;
            $response['pesan'] = 'Parameter tidak terdaftar';
        }

        echo json_encode($response);
    }

    public function cancel_to() {
        $response = array();
        $tanggal = date('Y-m-d');
        $waktu = date('H:i:s');
        $param = $_POST['param'];
        $kd_trx = $_POST['kd_trx'];
        $kd_brg = $_POST['kd_brg'];
        $urutan = $_POST['urutan'];
        $qty_before = (int)$_POST['qty_before'];
        $qty = (int)$_POST['qty'];
        $ket = $_POST['ket'];
        $lokasi = $this->m_crud->get_data("master_to", "lokasi", "no_to='".$kd_trx."'");

        $this->db->trans_begin();

        $get_master = $this->m_crud->get_data("master_to", "kd_waitres, atas_nama, no_meja", "no_to='".$kd_trx."'");

        $kitchen_print = array();
        $get_printer = $this->m_crud->read_data("kitchen_printer", "id_printer, konektor, vid, pid, ip");
        foreach ($get_printer as $item) {
            array_push($kitchen_print, array('server'=>$item['ip'], 'konektor'=>$item['konektor'], 'vid'=>$item['vid'], 'pid'=>$item['pid'], 'kd_trx'=>$kd_trx, 'tanggal'=>$tanggal, 'waktu'=>$waktu, 'waitres'=>$this->m_crud->get_data("sales", "nama", "kode='".$get_master['kd_waitres']."'")['nama'], 'atas_nama'=>$get_master['atas_nama'], 'no_meja'=>$get_master['no_meja'], 'status'=>'Pesanan Batal', 'list'=>array()));
        }

        if ($param == 'trx') {
            $read_data = $this->m_crud->join_data("detail_to dt", "dt.kd_brg, dt.qty, dt.kcp, dt.urutan, br.nm_brg", "barang br", "br.kd_brg=dt.kd_brg", "dt.no_to='".$kd_trx."' and dt.status='P'", "dt.urutan ASC");
            foreach ($read_data as $item) {
                if ($item['kcp'] != '' || $item['kcp'] != null) {
                    $found_key = array_search($item['kcp'], array_column($get_printer, 'id_printer'));
                    $status_print = 'Pesanan Batal';
                    array_push($kitchen_print[$found_key]['list'], array('kd_brg' => $item['kd_brg'], 'nm_brg' => $item['nm_brg'], 'ket'=>$ket, 'qty' => abs($item['qty']), 'urutan'=>$item['urutan'], 'status' => $status_print));
                }
            }

            $upd_to = array('status'=>'C', 'ket_void'=>$ket);
            $this->m_crud->update_data("master_to", $upd_to, "no_to='".$kd_trx."'");
            /*Insert log*/
            $log = array(
                'type' => 'U',
                'table' => "master_to",
                'data' => $upd_to,
                'condition' => "no_to='".$kd_trx."'"
            );

            $data_log = array(
                'lokasi' => $this->config->item('lokasi'),
                'hostname' => '-',
                'db_name' => '-',
                'query' => json_encode($log)
            );
            $this->m_website->insert_log_tr($data_log);
            /*End insert log*/

            $upd_to = array('status'=>'C', 'ket_void'=>$ket, 'status_kcp'=>'0');
            $this->m_crud->update_data("detail_to", $upd_to, "no_to='".$kd_trx."'");
            /*Insert log*/
            $log = array(
                'type' => 'U',
                'table' => "detail_to",
                'data' => $upd_to,
                'condition' => "no_to='".$kd_trx."'"
            );

            $data_log = array(
                'lokasi' => $this->config->item('lokasi'),
                'hostname' => '-',
                'db_name' => '-',
                'query' => json_encode($log)
            );
            $this->m_website->insert_log_tr($data_log);
            /*End insert log*/
        } else if ($param == 'brg') {
            if ($qty == $qty_before) {
                $upd_to = array('status'=>'C', 'ket_void'=>$ket, 'status_kcp'=>'0');
                $this->m_crud->update_data("detail_to", $upd_to, "no_to='".$kd_trx."' and kd_brg='".$kd_brg."' and urutan='".$urutan."' and status='P'");

                /*Insert log*/
                $log = array(
                    'type' => 'U',
                    'table' => "detail_to",
                    'data' => $upd_to,
                    'condition' => "no_to='".$kd_trx."' and kd_brg='".$kd_brg."' and urutan='".$urutan."' and status='P'"
                );

                $data_log = array(
                    'lokasi' => $this->config->item('lokasi'),
                    'hostname' => '-',
                    'db_name' => '-',
                    'query' => json_encode($log)
                );
                $this->m_website->insert_log_tr($data_log);
                /*End insert log*/
            } else {
                $item = $this->m_crud->get_data("detail_to", "*", "no_to='".$kd_trx."' and kd_brg='".$kd_brg."' and urutan='".$urutan."'");

                $upd_to = array('qty'=>$qty_before-$qty, 'ket_void'=>'');
                $this->m_crud->update_data("detail_to", $upd_to, "no_to='".$kd_trx."' and kd_brg='".$kd_brg."' and urutan='".$urutan."' and status='P'");

                /*Insert log*/
                $log = array(
                    'type' => 'U',
                    'table' => "detail_to",
                    'data' => $upd_to,
                    'condition' => "no_to='".$kd_trx."' and kd_brg='".$kd_brg."' and urutan='".$urutan."' and status='P'"
                );

                $data_log = array(
                    'lokasi' => $this->config->item('lokasi'),
                    'hostname' => '-',
                    'db_name' => '-',
                    'query' => json_encode($log)
                );
                $this->m_website->insert_log_tr($data_log);
                /*End insert log*/

                $item['qty'] = $qty;
                $item['tanggal'] = $tanggal;
                $item['waktu'] = $waktu;
                $item['status'] = 'C';
                $item['status_kcp'] = '0';
                $item['ket_void'] = $ket;
                $this->m_crud->create_data("detail_to", $item);

                /*Insert log*/
                $log = array(
                    'type' => 'I',
                    'table' => "detail_to",
                    'data' => $item,
                    'condition' => ""
                );

                $data_log = array(
                    'lokasi' => $this->config->item('lokasi'),
                    'hostname' => '-',
                    'db_name' => '-',
                    'query' => json_encode($log)
                );
                $this->m_website->insert_log_tr($data_log);
                /*End insert log*/
            }

            $item = $this->m_crud->get_join_data("detail_to dt", "dt.kd_brg, dt.qty, dt.kcp, dt.urutan, br.nm_brg", "barang br", "br.kd_brg=dt.kd_brg", "dt.no_to='".$kd_trx."' and dt.kd_brg='".$kd_brg."' and urutan='".$urutan."' and dt.status_kcp='0' and dt.status='C'");
            if ($item['kcp'] != '' || $item['kcp'] != null) {
                $found_key = array_search($item['kcp'], array_column($get_printer, 'id_printer'));
                $status_print = 'Pesanan Batal';
                array_push($kitchen_print[$found_key]['list'], array('kd_brg' => $item['kd_brg'], 'nm_brg' => $item['nm_brg'], 'ket' => $ket, 'qty' => $item['qty'], 'urutan' => $item['urutan'], 'status' => $status_print));
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $response['status'] = false;
            $response['pesan'] = 'Pesanan gagal dibatalkan';
            $response['kd_trx'] = $kd_trx;
        } else {
            $this->db->trans_commit();
            $response['status'] = true;
            $response['pesan'] = 'Pesanan berhasil dibatalkan';
            $response['kd_trx'] = $kd_trx;
            $response['print'] = $this->m_website->insert_log_print($kitchen_print, 'to', $lokasi);
        }

        echo json_encode($response);
    }

    public function pending_print() {
        $response = array();

        $get_data = $this->m_crud->read_data("log_print", "id_log, app, data_print, head_foot", "status='0'");

        $response['data'] = $get_data;

        echo json_encode($response);
    }

    public function print_success() {
        $response = array();
        $log = $_POST['log'];
        if (isset($_POST['data'])) {
            $data = json_decode($_POST['data'], true); //{"kd_trx":"KODE TRANSAKSI","kd_brg":"KODE BARANG","urutan":"URUTAN CART"}
        } else {
            $data = null;
        }

        $this->db->trans_begin();

        $this->m_crud->update_data("log_print", array('status'=>'1'), "id_log='".$log."'");

        if (is_array($data) && count($data) > 0 && $data != null) {
            foreach ($data as $item) {
                $this->m_crud->update_data("detail_to", array('status_kcp'=>'1'), "no_to='".$item['kd_trx']."' and kd_brg='".$item['kd_brg']."' and urutan='".$item['urutan']."'");
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $response['status'] = false;
            $response['pesan'] = 'Data gagal disimpan!';
        } else {
            $this->db->trans_commit();
            $response['status'] = true;
            $response['pesan'] = 'Data berhasil disimpan!';
        }

        echo json_encode($response);
    }

    public function update_api() {
        $c_hostname = $this->db->hostname; $c_username = $this->db->username; $c_password = $this->db->password; $c_database = $this->db->database;
        $read_data = $this->m_crud->read_data("log_api", "id_log, hostname, db_name, query", "status='0'");
        $response = array();
        if ($read_data != null) {
            foreach ($read_data as $item) {
                $query = json_decode($item['query'], true);
                $config_app = change_db($item['hostname'].'\SQLEXPRESS', 'sa', '123456789K', $item['db_name']);
                $this->db = $this->load->database($config_app, TRUE);

                if($this->db->conn_id) {
                    $this->db->query($item['query']);
                    if ($query['type'] == 'I') {
                        $this->m_crud->create_data($query['table'], $query['data']);
                    } else if ($query['type'] == 'U') {
                        $this->m_crud->update_data($query['table'], $query['data'], $query['condition']);
                    }

                    $config_app = change_db($c_hostname, $c_username, $c_password, $c_database);
                    $this->db = $this->load->database($config_app, TRUE);

                    $this->m_crud->update_data("log_api", array('status'=>'1'), "id_log='".$item['id_log']."'");
                }
            }
        }

        echo json_encode(array('status'=>true, 'response'=>$response));
    }

    public function simpan_req() {
        $response = array();
        $param = $_POST['param'];

        $this->db->trans_begin();

        $max_code = $this->m_crud->get_data("request_pesanan", "max(cast(id_request as int)) max_data");

        if ($param == 'new') {
            $this->m_crud->create_data("request_pesanan", array('id_request'=>$max_code['max_data'] + 1, 'pesan'=>ucwords($_POST['pesan'])));
        } else if ($param == 'update') {
            $this->m_crud->update_data("request_pesanan", array('pesan'=>ucwords($_POST['pesan'])), "id_request='".$_POST['id']."'");
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $response['status'] = false;
            $response['pesan'] = 'Data gagal disimpan';
        } else {
            $this->db->trans_commit();
            $response['status'] = true;
            $response['pesan'] = 'Data berhasil disimpan';
        }

        echo json_encode($response);
    }

    public function activity($perpage=10, $page=1) {
        $response = array();
        $where = "mt.status = 'P'";
        $cari = $_POST['cari'];

        $start = ($page-1)*$perpage+1;
        $end = $perpage*$page;

        if (isset($cari) && $cari != '') {
            $where .= $where==null?'':' AND ';
            $where .= "(mt.no_to LIKE '%".$cari."%' OR mt.atas_nama LIKE '%".$cari."%' OR mt.kd_waitres LIKE '%".$cari."%' OR mt.no_meja LIKE '%".$cari."%')";
        }

        $read_data = $this->m_crud->select_limit_join('master_to mt', "mt.no_to, mt.tanggal, mt.waktu, isnull(s.nama, 'UMUM') nm_waitres, mt.kd_waitres, mt.atas_nama, mt.meja, mt.no_meja, mt.jml_tamu, mt.kd_admin, mt.status, mt.type_order, mt.lokasi, SUM(dt.qty * dt.hrg_jual) st, MAX(dt.urutan) max_urutan", array("detail_to dt", array('table'=>'sales s', 'type'=>'LEFT')), array("dt.no_to=mt.no_to", "mt.kd_waitres=s.kode"), $where, 'mt.tanggal desc, mt.waktu desc', "mt.no_to, mt.tanggal, mt.waktu, s.nama, mt.kd_waitres, mt.atas_nama, mt.meja, mt.no_meja, mt.jml_tamu, mt.kd_admin, mt.status, mt.type_order, mt.lokasi", $start, $end);

        if ($read_data == null) {
            $response['status'] = false;
        } else {
            foreach ($read_data as $key => $item) {
                $read_data[$key]['tgl_formated'] = date('Y-m-d', strtotime($item['tanggal'])) . ' ' . substr($item['waktu'], 0, 8);
                $read_data[$key]['barang'] = $this->m_website->tambah_data('barang', $this->m_crud->read_data("detail_to dt, barang br", "dt.*, br.barcode, br.nm_brg, br.satuan, br.gambar, br.hrg_jual_1", "dt.kd_brg=br.kd_brg AND dt.no_to = '" . $item['no_to'] . "' AND dt.status='P'", "dt.urutan ASC"), $this->config->item('lokasi'));
            }

            $response['status'] = true;
            $response['data'] = $read_data;
        }

        echo json_encode($response);
    }

    public function get_lokasi() {
        $response = array();

        $user = $_POST['kd_kasir'];

        $get_data = $this->m_crud->get_data("user_akun", "lokasi", "user_id='".$user."'");

        if ($get_data != null) {
            $lokasi = json_decode($get_data['lokasi'], true);

            $lokasi_in = array();
            foreach ($lokasi['lokasi_list'] as $item) {
                array_push($lokasi_in, '\''.$item['kode'].'\'');
            }

            $get_lokasi = $this->m_crud->read_data('lokasi', "kode, nama_toko nama, ket alamat, serial, nama header1, ket header2, kota header3, web header4, footer1, footer2, footer3, footer4", "kode in (".implode(',', $lokasi_in).")");

            $lokasi_list = array();
            foreach ($get_lokasi as $item) {
                $head_foot = array(
                    'header1' => $item['header1'],
                    'header2' => $item['header2'],
                    'header3' => $item['header3'],
                    'header4' => $item['header4'],
                    'footer1' => $item['footer1'],
                    'footer2' => $item['footer2'],
                    'footer3' => $item['footer3'],
                    'footer4' => $item['footer4']
                );
                array_push($lokasi_list, array(
                    'kode' => $item['kode'],
                    'nama' => $item['nama'],
                    'alamat' => $item['alamat'],
                    'serial' => $item['serial'],
                    'header_footer' => $head_foot
                ));
            }

            $response['status'] = true;
            $response['data'] = $lokasi_list;
        } else {
            $response['status'] = false;
            $response['pesan'] = 'Data tidak tersedia';
        }

        echo json_encode($response);
    }

    /*$this->db->last_query()*/
    /*END API TO*/

    public function request_api_node() {
        if (isset($_POST['datapost'])) {
            $response = array(
                'status' => true,
                'pesan' => $_POST['datapost']
            );
        } else {
            $response = array(
                'status' => true,
                'pesan' => 'Request Berhasil'
            );
        }

        echo json_encode($response);
    }

    public function get_log() {
        $response = array();

        $lokasi = $_POST['lokasi'];

        if ($lokasi != null && $lokasi != '') {
            $get_data = $this->m_crud->get_data("log_api", "*", "status='0' AND lokasi='".$lokasi."' AND param='send'", "id_log DESC");

            if ($get_data != null) {
                $response['status'] = true;
                $response['data'] = $get_data;
            } else {
                $response['status'] = false;
            }
        } else {
            $response['status'] = false;
            $response['pesan'] = 'Parameter tidak terdaftar';
        }

        echo json_encode($response);
    }

    public function insert_log() {
        $response = array();

        $id_log = '';
        $data = json_decode(base64_decode($_POST['data']), true);
        $data_log = $data['data'];

        $this->db->trans_begin();

        if ($data['status']) {
            $cek_log = $this->m_crud->get_data("log_api", "id_log", "id_log='" . $data_log['id_log'] . "'");

            if ($cek_log == null) {
                $data_log['param'] = 'exec';
                $this->m_crud->create_data("log_api", $data_log);
            }

            $id_log = $data_log['id_log'];
        }

        if ($this->db->trans_status() === false && $data['status'] === false) {
            $this->db->trans_rollback();
            $response['status'] = false;
        } else {
            $this->db->trans_commit();
            $response['status'] = true;
            $response['id'] = $id_log;
        }

        echo json_encode($response);
    }

    public function success_log() {
        $response = array();

        $status = $_POST['status'];
        $id = $_POST['id'];

        $this->db->trans_begin();

        if ($status == 'true' || $status == true) {
            $this->m_crud->update_data("log_api", array('status'=>'1'), "id_log='".$id."'");
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $response['status'] = false;
        } else {
            $this->db->trans_commit();
            $response['status'] = true;
        }

        echo json_encode($response);
    }

    public function exec_log() {
        $response = array();

        //$get_log = $this->m_crud->read_data("log_api", "*", "status='0' AND param='exec' AND lokasi='".$_POST['lokasi']."'");
        $get_log = $this->m_crud->read_data("log_api", "*", "status='0' AND param='exec'");

        if ($get_log != null) {
            foreach ($get_log as $item) {
                $this->db->trans_begin();

                $data = json_decode($item['query'], true);

                $cek_log = $this->m_crud->get_data("exec_log", "id_log", "id_log='".$item['id_log']."'");

                if ($cek_log == null) {
                    if ($data['type'] == 'I') {
                        $this->m_crud->create_data($data['table'], $data['data']);
                    } else if ($data['type'] == 'U') {
                        $this->m_crud->update_data($data['table'], $data['data'], $data['condition']);
                    } else if ($data['type'] == 'D') {
                        $this->m_crud->delete_data($data['table'], $data['condition']);
                    } else if ($data['type'] == 'Q') {
                        $this->db->query($data['data']);
                    }
                }

                $err = $this->db->error();

                if ($this->db->trans_status() === false) {
                    if ($err['code'] == '23000/2627') {
                        $this->m_crud->create_data("exec_log", array('id_log'=>$item['id_log'], 'tanggal'=>date('Y-m-d H:i:s')));
                        $this->m_crud->update_data("log_api", array('status'=>'1'), "id_log='".$item['id_log']."'");
                        $this->db->trans_commit();
                    } else {
                        $this->db->trans_rollback();
                    }
                } else {
                    $this->m_crud->create_data("exec_log", array('id_log'=>$item['id_log'], 'tanggal'=>date('Y-m-d H:i:s')));
                    $this->m_crud->update_data("log_api", array('status'=>'1'), "id_log='".$item['id_log']."'");
                    $this->db->trans_commit();
                }
            }

            $response['pesan'] = 'Data berhasil di eksekusi';
        } else {
            $response['pesan'] = 'Data log masih kosong';
        }

        echo json_encode($response);
    }

    public function rajaongkir_provinsi() {
        $data = json_decode($this->m_website->rajaongkir_provinsi(), true);

        $res_data = $data['rajaongkir']['results'];
        foreach ($res_data as $item) {
            $this->m_crud->create_data("provinsi_rajaongkir", array('provinsi_id'=>$item['province_id'], 'provinsi'=>$item['province']));
        }
    }

    public function rajaongkir_kota() {
        $data = json_decode($this->m_website->rajaongkir_kota(), true);

        $res_data = $data['rajaongkir']['results'];
        foreach ($res_data as $item) {
            $this->m_crud->create_data("kota_rajaongkir", array('kota_id'=>$item['city_id'], 'provinsi'=>$item['province_id'], 'kota'=>$item['city_name'], 'tipe'=>$item['type'], 'postal_code'=>$item['postal_code']));
        }
    }

    public function rajaongkir_kecamatan() {
        $get_kota = $this->m_crud->read_data("kota_rajaongkir", "kota_id");

        foreach ($get_kota as $item) {
            $data = json_decode($this->m_website->rajaongkir_kecamatan($item['kota_id']), true);

            $res_data = $data['rajaongkir']['results'];
            foreach ($res_data as $item) {
                $this->m_crud->create_data("kecamatan_rajaongkir", array('kecamatan_id'=>$item['subdistrict_id'], 'kota'=>$item['city_id'], 'kecamatan'=>$item['subdistrict_name']));
            }
        }
    }

    public function get_transfer() {
        $result = array();
        $id_pembayaran = $_POST['id_pembayaran'];

        $get_bukti = $this->m_crud->get_data("pembayaran", "atas_nama, bank, no_rek, (jumlah + kode_unik) total, ('".base_url()."' + bukti_transfer) gambar", "id_pembayaran='".$id_pembayaran."'");

        if ($get_bukti != null) {
            $result['status'] = true;
            $result['res_transfer'] = $get_bukti;
        } else {
            $result['status'] = false;
        }

        echo json_encode($result);
    }

    public function lacak_resi()
    {
        $data = array(
            'kurir' => $_POST['kurir'],
            'resi' => $_POST['resi']
        );

        echo $this->m_website->rajaongkir_resi(json_encode($data));
    }

    public function update_query() {
        $table = "kas_masuk";
        $master = array();
        $condition = "kd_trx = 'KM-1811010001-G'";
        $this->m_crud->delete_data($table, $condition);

        $read_lokasi = $this->m_crud->read_data('Lokasi', 'Kode, Nama, server, db_name', "Kode='LK/0006'");

        foreach ($read_lokasi as $item_lokasi) {
            $log = array(
                'type' => 'D',
                'table' => $table,
                'data' => $master,
                'condition' => $condition
            );

            $data_log = array(
                'lokasi' => $item_lokasi['Kode'],
                'hostname' => $item_lokasi['server'],
                'db_name' => $item_lokasi['db_name'],
                'query' => json_encode($log)
            );
            $this->m_website->insert_log_api($data_log);
        }
    }

    public function res_json() {
        $foto = 'https://cdn1.iconfinder.com/data/icons/mix-color-4/502/Untitled-1-512.png';
        $json = '{"status":true,"data":[{"user_id":"2021","name":"Bones","foto":"'.$foto.'","date":"2018-10-30 09:33:19","message":"Test"},{"user_id":"0002","name":"Aca","foto":"'.$foto.'","date":"2018-10-29 12:11:44","message":"Test Inbox"},{"user_id":"0003","name":"Ana","foto":"'.$foto.'","date":"2018-10-28 15:23:22","message":"Hallo"},{"user_id":"0003","name":"Ana","foto":"'.$foto.'","date":"2018-10-28 16:15:11","message":"Hai"}],"badge":"100"}';

        echo $json;
    }

    public function cek() {
        $res = array(
            'success' => 1,
            'message' => 'Data berhasil disimpan',
            'notif' => array(
                'notif_id' => '1',
                'receiver_id' => '1',
                'user_nama' => '1',
                'notif_foto' => '1',
                'notif_text' => '1',
                'notif_text_list' => 'Notif untuk <a href="'.base_url().'/project/project_detail/1">Projek Baru</a>',
                'notif_link' => '1',
                'notif_date' => '1',
                'notif_read' => '1'
            )
        );

        echo json_encode($res);
    }

    public function insert_barang() {
        $get_barang = $this->m_crud->read_data("barang_", "*");


        $this->db->trans_begin();
        foreach ($get_barang as $item) {
            $kd_brg = 'SLS'.$item['fcode'];
            if ($item['fkitchen_id'] == '1') {
                $gr2 = '01';
                $kcp = 'KP0001';
                $srv = 5;
                $tax = 10;
            } else if ($item['fkitchen_id'] == '2') {
                $gr2 = '02';
                $kcp = 'KP0002';
                $srv = 5;
                $tax = 10;
            } else {
                $gr2 = '05';
                $kcp = '';
                $srv = 0;
                $tax = 0;
            }
            $master = array(
                'kd_brg' => $kd_brg,
                'barcode' => $kd_brg,
                'nm_brg' => $item['fname'],
                'Deskripsi' => $item['fdesc'],
                'kel_brg' => '0601',
                'Group1' => '-',
                'Group2' => $gr2,
                'satuan' => 'PORSI',
                'hrg_beli' => 0,
                'stock_min' => 0,
                'kategori' => 'Non Paket',
                'Jenis' => 'Barang Dijual',
                'tgl_input' => date('Y-m-d H:i:s'),
                'kd_packing' => '-',
                'qty_packing' => 0,
                'barang_online' => 0,
                'hrg_jual_1' => $item['fprice'],
                'service' => $srv,
                'PPN' => $tax,
                'gambar' => '-',
                'kcp' => $kcp,
                'berat' => 0,
                'poin' => 0,
                'online' => 0
            );

            $this->m_crud->create_data('barang', $master);
        }

        if ($this->db->trans_status() === true) {
            $this->db->trans_commit();
        } else {
            $this->db->trans_rollback();
        }
    }

    public function get_meja() {
        $response = array();
        $area = $_POST['id_area'];

        $get_meja = $this->m_crud->join_data("data_meja m", "m.*, (m.nama_area + ' ' + m.nama_meja) text, isnull(mt.no_to, '') no_to, isnull(mt.tanggal, '') tanggal, isnull(mt.waktu, '') waktu, isnull(mt.jml_tamu, '') jml_tamu, isnull(mt.atas_nama, '') atas_nama, isnull(mt.gabung, '') gabung, isnull(s.nama, '') waitres", array(array('type'=>'LEFT','table'=>'master_to mt'), array('type'=>'LEFT','table'=>'sales s')), array("mt.meja=m.id_meja and mt.status='P'", "s.Kode=mt.kd_waitres"), "m.id_area='".$area."'");

        if ($get_meja != null) {
            $response['status'] = true;
            $response['data'] = $get_meja;
        } else {
            $response['status'] = false;
        }

        echo json_encode($response);
    }

    public function otorisasi() {
        $response = array();
        $user_id = $_POST['user_id'];
        $otorisasi = $_POST['otorisasi'];

        $cek_data = $this->m_crud->get_data("user_akun", "user_id", "user_id='".$user_id."' and password_otorisasi='".$otorisasi."'");

        if ($cek_data != null) {
            $response['status'] = true;
            $response['pesan'] = 'Otorisasi berhasil';
        } else {
            $response['status'] = false;
            $response['pesan'] = 'Otorisasi salah';
        }

        echo json_encode($response);
    }

    public function login_waitres() {
        $response = array();

        $lokasi = $_POST['lokasi'];
        $username = $_POST['username'];

        $get_data = $this->m_crud->get_data("sales", "kode, nama", "status='1' and lokasi='".$lokasi."' and username='".$username."'");

        if ($get_data != null) {
            $response['status'] = true;
            $response['pesan'] = 'Login Berhasil';
            $response['data'] = $get_data;
        } else {
            $response['status'] = false;
            $response['pesan'] = 'Login Gagal';
        }

        echo json_encode($response);
    }

    public function gabung_meja() {
        $response = array();

        $no_to = json_decode($_POST['no_to'], true);
        $gabung = $this->m_website->generate_kode('gabung', $this->config->item('lokasi'), date('ymd'));

        $this->db->trans_begin();

        if (isset($_POST['gabung']) && $_POST['gabung']!='') {
            $gabung = $_POST['gabung'];
            $this->m_crud->update_data("master_to", array('gabung'=>''), "gabung='".$gabung."'");
            /*Insert log*/
            $log = array(
                'type' => 'U',
                'table' => "master_to",
                'data' => array('gabung'=>''),
                'condition' => "gabung='".$gabung."'"
            );

            $data_log = array(
                'lokasi' => $this->config->item('lokasi'),
                'hostname' => '-',
                'db_name' => '-',
                'query' => json_encode($log)
            );
            $this->m_website->insert_log_tr($data_log);
            /*End insert log*/
        }

        if (count($no_to) > 1) {
            foreach ($no_to as $item) {
                $this->m_crud->update_data("master_to", array('gabung' => $gabung), "no_to='" . $item['no_to'] . "'");
                /*Insert log*/
                $log = array(
                    'type' => 'U',
                    'table' => "master_to",
                    'data' => array('gabung'=>$gabung),
                    'condition' => "no_to='" . $item['no_to'] . "'"
                );

                $data_log = array(
                    'lokasi' => $this->config->item('lokasi'),
                    'hostname' => '-',
                    'db_name' => '-',
                    'query' => json_encode($log)
                );
                $this->m_website->insert_log_tr($data_log);
                /*End insert log*/
            }
        }

        if ($this->db->trans_status() === true) {
            $this->db->trans_commit();
            $response['status'] = true;
            $response['pesan'] = 'Data berhasil disimpan';
        } else {
            $this->db->trans_rollback();
            $response['status'] = false;
            $response['pesan'] = 'Data gagal disimpan';
        }

        echo json_encode($response);
    }

    public function edit_meja() {
        $response = array();

        $gabung = $_POST['gabung'];

        $get_meja = $this->m_crud->read_data("master_to", "no_to", "gabung='".$gabung."'");

        if ($get_meja != null) {
            $response['status'] = true;
            $response['data'] = $get_meja;
        } else {
            $response['status'] = false;
            $response['pesan'] = "Data tidak tersedia!";
        }

        echo json_encode($response);
    }
}
