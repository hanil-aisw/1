<?php
// index.php
// PHP 환경에서 동작하지만, 실시간 데이터 처리를 위해 Firebase JavaScript SDK를 활용한 SPA(Single Page Application) 형태로 구현되었습니다.
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <style>
        /* 다크 테마 및 글래스모피즘 UI 스타일링 */
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e1e2f, #121212);
            color: #ffffff;
            font-family: 'Pretendard', -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .glass-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 30px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }
        h1 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 25px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        input[type="text"] {
            flex: 1;
            padding: 14px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: border 0.3s ease;
        }
        input[type="text"]:focus {
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        input[type="text"]::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        button.add-btn {
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.1s ease;
        }
        button.add-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        button.add-btn:active {
            transform: scale(0.97);
        }
        ul {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        /* 스크롤바 스타일링 */
        ul::-webkit-scrollbar {
            width: 6px;
        }
        ul::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        li:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        li.completed span {
            text-decoration: line-through;
            color: rgba(255, 255, 255, 0.3);
        }
        .task-text {
            cursor: pointer;
            flex: 1;
            font-size: 15px;
            word-break: break-word;
        }
        .delete-btn {
            background: rgba(255, 80, 80, 0.15);
            border: 1px solid rgba(255, 80, 80, 0.3);
            color: #ff6b6b;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        .delete-btn:hover {
            background: rgba(255, 80, 80, 0.3);
            color: #fff;
        }
    </style>
</head>
<body>

<div class="glass-container">
    <h1>📝 My To-Do</h1>
    <div class="input-group">
        <input type="text" id="taskInput" placeholder="새로운 할 일을 입력하세요...">
        <button class="add-btn" id="addBtn">추가</button>
    </div>
    <ul id="taskList">
        </ul>
</div>

<script type="module">
    // Firebase 핵심 모듈 및 Firestore 모듈 임포트
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-app.js";
    import { getFirestore, collection, addDoc, onSnapshot, deleteDoc, doc, updateDoc, query, orderBy, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-firestore.js";

    // 🔴 중요: 여기에 본인의 Firebase 프로젝트 설정값을 입력하셔야 합니다.
    const firebaseConfig = {
  apiKey: "AIzaSyCu4vJkXNHD6fxcOxAVmMrsTQSMWG8VOIM",
  authDomain: "todo-b0595.firebaseapp.com",
  projectId: "todo-b0595",
  storageBucket: "todo-b0595.firebasestorage.app",
  messagingSenderId: "981458118531",
  appId: "1:981458118531:web:8b367d8ac72099b1bab8b6"
};

    // Firebase 초기화
    const app = initializeApp(firebaseConfig);
    const db = getFirestore(app);
    
    // Firestore의 'todos' 컬렉션 참조
    const taskCollection = collection(db, "todos");

    // DOM 요소 참조
    const taskInput = document.getElementById('taskInput');
    const addBtn = document.getElementById('addBtn');
    const taskList = document.getElementById('taskList');

    // 1. 할 일 추가 (Create)
    async function addTask() {
        const taskText = taskInput.value.trim();
        if (taskText === "") {
            alert("할 일을 입력해주세요.");
            return;
        }

        try {
            await addDoc(taskCollection, {
                text: taskText,
                completed: false,
                createdAt: serverTimestamp() // 정렬을 위한 타임스탬프
            });
            taskInput.value = ""; // 입력창 초기화
        } catch (error) {
            console.error("문서 추가 중 오류 발생: ", error);
            alert("데이터베이스 권한을 확인해주세요. (Firestore 보안 규칙이 읽기/쓰기를 허용해야 합니다)");
        }
    }

    // 엔터키 입력 및 버튼 클릭 이벤트 등록
    addBtn.addEventListener('click', addTask);
    taskInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            addTask();
        }
    });

    // 2. 실시간 데이터 가져오기 및 렌더링 (Read)
    // 생성 시간 기준 내림차순 정렬
    const q = query(taskCollection, orderBy("createdAt", "desc"));
    
    // onSnapshot을 사용하면 데이터베이스가 변경될 때마다 자동으로 화면이 업데이트됩니다.
    onSnapshot(q, (snapshot) => {
        taskList.innerHTML = ""; // 기존 목록 초기화
        
        snapshot.forEach((docSnapshot) => {
            const task = docSnapshot.data();
            const taskId = docSnapshot.id;

            // li 엘리먼트 생성
            const li = document.createElement('li');
            if (task.completed) {
                li.classList.add('completed');
            }

            // 텍스트 영역 (클릭 시 완료 상태 토글)
            const span = document.createElement('span');
            span.className = 'task-text';
            span.textContent = task.text;
            span.onclick = () => toggleTask(taskId, task.completed);

            // 삭제 버튼
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-btn';
            deleteBtn.textContent = '삭제';
            deleteBtn.onclick = () => deleteTask(taskId);

            // 요소 조합
            li.appendChild(span);
            li.appendChild(deleteBtn);
            taskList.appendChild(li);
        });
    });

    // 3. 할 일 완료 상태 토글 (Update)
    async function toggleTask(id, currentStatus) {
        try {
            const taskDoc = doc(db, "todos", id);
            await updateDoc(taskDoc, {
                completed: !currentStatus
            });
        } catch (error) {
            console.error("상태 업데이트 중 오류 발생: ", error);
        }
    }

    // 4. 할 일 삭제 (Delete)
    async function deleteTask(id) {
        if (confirm("이 항목을 정말 삭제하시겠습니까?")) {
            try {
                const taskDoc = doc(db, "todos", id);
                await deleteDoc(taskDoc);
            } catch (error) {
                console.error("문서 삭제 중 오류 발생: ", error);
            }
        }
    }
</script>
</body>
</html>
